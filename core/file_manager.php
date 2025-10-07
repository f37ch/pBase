<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
require("config.php");
require("db.php");
require_once("steamauth/steamauth.php");
require_once("cache.php");
if (!isset($_SESSION["steamid"])){
    http_response_code(403);
    die(json_encode(array("error"=>"Access denied.")));
}
$limitsize=$settings["storage"]["filesize_limit"];
$storagelimit=$settings["storage"]["storage_limit"];
$storagemaxf=$settings["storage"]["storage_maxfiles"];
$storageroot="..".DIRECTORY_SEPARATOR."storage".DIRECTORY_SEPARATOR;
$user_storageroot=$storageroot.$_SESSION["steamid"].DIRECTORY_SEPARATOR;
$allowedext = $settings["storage"]["allowed_extensions"];
$storageinf=dir_size($user_storageroot);
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
function format_size($size) {
    $mod=1024;
    $units=explode(" ","B KB MB GB TB PB");
    for ($i=0;$size>$mod;$i++) {
        $size/=$mod;
    }
    return round($size,2)." ".$units[$i];
}
function dir_size($directory) {
    if(!is_dir($directory)) {
        mkdir($directory);
    }
    $size=0;
    $cnt=0;
    foreach(new FilesystemIterator($directory) as $file){
        $size+=$file->getSize();
        $cnt++;
    }
    return array("size"=>$size,"cnt"=>$cnt);
}
function calc_percent($val,$percent)
{
	return $val*($percent/100); 
}
function filter_filename($filename) {
    // Allow letters, numbers, dots, and Cyrillic characters
    $filename=preg_replace("/[^a-zA-Z0-9.а-яА-ЯёЁ]/u","",$filename);

    // Remove leading dots and hyphens
    $filename=ltrim($filename,".-");

    // Extract the extension
    $ext=pathinfo($filename,PATHINFO_EXTENSION);

    // Truncate filename to 50 bytes (accounting for multibyte characters)
    $encoding=mb_detect_encoding($filename);
    $filename=mb_strcut(pathinfo($filename,PATHINFO_FILENAME),0,50-($ext ? strlen($ext)+1 : 0),$encoding);

    // Re-add the extension if it exists
    $filename=$filename.($ext ? ".".$ext : "");

    return $filename;
}
if (!Cache::get("storage_check")){
    $dirs=glob($storageroot."*",GLOB_ONLYDIR+GLOB_NOSORT);
    foreach($dirs as $dir){
        if (count(glob("$dir/*",GLOB_NOSORT))===0&&basename($dir)!=$_SESSION["steamid"]){
            rmdir($dir);
        }elseif($settings["storage"]["autodelete"]){
            $actualsid=basename($dir);
            $userdata=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$actualsid';")->fetch_assoc();
            if (!isset($userdata["last_played"])||(time()-$userdata["last_played"])>$settings["storage"]["unactive_time"]){
                array_map("unlink",glob("$dir/*.*"));
                rmdir($dir);
            }
        }
    }
    Cache::put("storage_check",":D",86400);
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_POST["file_submit"]))
{
    $sid=$_SESSION["steamid"];
    if(getUserGroup()==5){
        echo json_encode(["error"=>"Вы были забанены."]);
        return;
    }
    $resultsv=$GLOBALS["database"]->query("SELECT * FROM servers;");
    while ($row=$resultsv->fetch_assoc()){
        $sv_name=$row["sv_name"];
        $banned=$GLOBALS["database"]->query("SELECT * FROM bans WHERE offender_steamid='$sid' and server='$sv_name' ORDER BY id DESC LIMIT 1;")->fetch_array();
        if (isset($banned)&&($banned["expires"]==0||$banned["expires"]>time())){
            echo json_encode(array("error"=>"Вы были забанены на одном из наших серверов. Только после разбана вам будет разрешено загружать файлы!"));
            return;
        }
    }
    $userdata=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$sid';")->fetch_assoc();
    unset($sid);
    if($settings["storage"]["require_activity"]&&(!isset($userdata["last_played"])||(time()-$userdata["last_played"])>$settings["storage"]["unactive_time"])){
        echo json_encode(array("error"=>"Чтобы воспользоваться хранилищем - вам нужно проявить активность на наших серверах, так как вы вовсе не играли у нас, либо не заходили более месяца!"));
    }else{
        $filename=filter_filename($_FILES["file"]["name"]);
        $size=$_FILES["file"]["size"];
        if ($size>$limitsize){
            echo json_encode(array("error"=>"Слишком большой размер файла!"));
        }elseif ($size+$storageinf["size"]>$storagelimit){
            echo json_encode(array("error"=>"Вы достигли лимита размера хранилища!"));
        }elseif ($storageinf["cnt"]>=$storagemaxf){
            echo json_encode(array("error"=>"Вы достигли лимита файлов хранилища!"));
        }elseif($filename != ""){
            $ext = pathinfo($filename,PATHINFO_EXTENSION);
            if (in_array($ext,$allowedext))
            {
                move_uploaded_file($_FILES["file"]["tmp_name"],$user_storageroot.uniqid().$filename);
                echo json_encode(array("success"=>"Файл успешно загружен!"));
            }else{
                echo json_encode(array("error"=>"Данный формат файла запрещен!"));
            }
        }else{
            echo json_encode(array("error"=>"Безымянный файл!"));
        }
    }
}
if (isset($_POST["file_delete"]))
{
    if (isset($_POST["sid"])&&!hasAccess("storagemoderate")){
        http_response_code(403);
        die(json_encode(array("error"=>"Access denied.")));
    }
    $sid=$_POST["sid"]??$_SESSION["steamid"];
    $path=$storageroot.$sid.DIRECTORY_SEPARATOR.basename($_POST["file_delete"]);
    if (!unlink($path)){
        echo json_encode(array("fm_mod"=>isset($_POST["sid"]),"error"=>"Произошла ошибка удаления файла!"));
    }else{
        echo json_encode(array("fm_mod"=>isset($_POST["sid"]),"success"=>"Файл успешно удалён!"));
    }
}
if (isset($_POST["file_list"]))
{
    if (isset($_POST["sid"])&&!hasAccess("storagemoderate")){
        http_response_code(403);
        die(json_encode(array("error"=>"Access denied.")));
    }
    $sid=$_POST["sid"]??$_SESSION["steamid"];
    $fileList=array();
    $filesroot=$storageroot.$sid.DIRECTORY_SEPARATOR;
    foreach (scandir($filesroot,SCANDIR_SORT_DESCENDING) as $file) {
        if ($file!=="."&&$file!=="..") {
            $extension = pathinfo($file,PATHINFO_EXTENSION);
            $fileList[]=array(
                "name"=>$file,
                "extension"=>$extension,
                "size"=>format_size(filesize($filesroot.$file))
            );
        }
    }
    $fileList["sid"]=$sid;
    $fileList["storagelimit"]=$storagelimit;
    if (!isset($_POST["sid"])){
        $fileList["spaceleft"]=$storagelimit-$storageinf["size"];
        $fileList["storagecnt"]=$storageinf["cnt"];
        $fileList["storagemaxcnt"]=$storagemaxf;
        $userdata=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$sid';")->fetch_assoc();
        if ($storageinf["cnt"]>0&&$settings["storage"]["require_activity"]&&isset($userdata["last_played"])&&(time()-$userdata["last_played"])>calc_percent($settings["storage"]["unactive_time"],80)){
            $fileList["warn"]="Если вы в ближайшее время не проявите активность на наших серверах, все ваши файлы будут удалены.";
        }
    }
    echo json_encode($fileList);
}
?>