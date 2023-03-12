<?php
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
require("config.php");
require("db.php");
require_once("steamauth/steamauth.php");
if (!isset($_SESSION["steamid"])){
    http_response_code(403);
    die(json_encode(array("error"=>"Access denied.")));
}
$limitsize=$settings["storage"]["filesize_limit"];
$storagelimit=$settings["storage"]["storage_limit"];
$storagemaxf=$settings["storage"]["storage_maxfiles"];
$storageroot="..".DIRECTORY_SEPARATOR."storage".DIRECTORY_SEPARATOR.$_SESSION["steamid"].DIRECTORY_SEPARATOR;
$allowedext = $settings["storage"]["allowed_extensions"];
$storageinf=dir_size($storageroot);
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
function format_size($size) {
    $mod = 1024;
    $units = explode(' ','B KB MB GB TB PB');
    for ($i = 0; $size > $mod; $i++) {
        $size /= $mod;
    }
    return round($size, 2) . ' ' . $units[$i];
}
function dir_size($directory) {
    if(!is_dir($directory)) {
        mkdir($directory);
    }
    $size = 0;
    $cnt=0;
    foreach(new FilesystemIterator($directory) as $file){
        $size += $file->getSize();
        $cnt++;
    }
    return array("size"=>$size,"cnt"=>$cnt);
}
//////////////////////////////////////////////////////////////////////////////////////////////////////////////
if (isset($_POST["file_submit"]))
{
    $sid=$_SESSION["steamid"];
    $userdata=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$sid';")->fetch_assoc();
    unset($sid);
    if ($settings["storage"]["require_activity"]&&(!isset($userdata["last_played"])||(time()-$userdata["last_played"])>2419200)){
        echo json_encode(array("error"=>"Чтобы воспользоваться хранилищем - вам нужно проявить активность на наших серверах, так как вы вовсе не играли у нас, либо не заходили более месяца!"));
    }else{
        $filename = $_FILES["file"]["name"];
        $size=$_FILES["file"]["size"];
        if ($size>$limitsize){
            echo json_encode(array("error"=>"Слишком большой размер файла!"));
            return;
        }
        if ($size+$storageinf["size"]>$storagelimit){
            echo json_encode(array("error"=>"Вы достигли лимита размера хранилища!"));
            return;
        }
        if ($storageinf["cnt"]>=$storagemaxf){
            echo json_encode(array("error"=>"Вы достигли лимита файлов хранилища!"));
            return;
        }
        if($filename != ""){
            $ext = pathinfo($filename, PATHINFO_EXTENSION);
            //check if file type is valid
            if (in_array($ext,$allowedext))
            {
                move_uploaded_file($_FILES["file"]["tmp_name"],$storageroot.uniqid().$filename);
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
    $path=$storageroot.DIRECTORY_SEPARATOR.$_POST["file_delete"];
    if (!unlink($path)){
        echo json_encode(array("error"=>"Произошла ошибка удаления файла!"));
    }else{
        echo json_encode(array("success"=>"Файл успешно удалён!"));
    }
}
if (isset($_POST["file_list"]))
{
    $ara=array_values(array_diff(scandir($storageroot),array('..', '.')));
    $ara["sid"]=$_SESSION["steamid"];
    $ara["storagelimit"]=$storagelimit;
    $ara["spaceleft"]=$storagelimit-$storageinf["size"];
    $ara["storagecnt"]=$storageinf["cnt"];
    $ara["storagemaxcnt"]=$storagemaxf;
    echo json_encode($ara);
}
?>