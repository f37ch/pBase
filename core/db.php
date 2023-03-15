<?php
require_once("config.php");
function InitDB($server="localhost",$user="root",$pass="",$dbname="pBase")
{
    $tables=[
        "CREATE TABLE IF NOT EXISTS users (
        steamid bigint(20) NOT NULL,
        name varchar(50) DEFAULT NULL,
        avatarfull MEDIUMTEXT DEFAULT NULL,
        registered int(11) DEFAULT NULL,
        last_played int(11) DEFAULT NULL,
        last_online int(11) DEFAULT NULL,
        PRIMARY KEY (steamid));",
        
        "CREATE TABLE IF NOT EXISTS servers(
        id int(11) NOT NULL AUTO_INCREMENT,
        sv_name VARCHAR(100) NOT NULL,
        sv_ip VARCHAR(100) NOT NULL,
        sv_port INT(20) NOT NULL,
        PRIMARY KEY (id));",

        "CREATE TABLE IF NOT EXISTS bans (
        id int(11) NOT NULL AUTO_INCREMENT,
        type VARCHAR(5) DEFAULT NULL,
        server VARCHAR(100) NOT NULL,
        offender_steamid bigint(20) NOT NULL,
        admin_steamid bigint(20) DEFAULT NULL,
        created int(11) DEFAULT NULL,
        expires int(11) DEFAULT NULL,
        reason varchar(500) DEFAULT NULL,
        PRIMARY KEY (id));",
    
        "CREATE TABLE IF NOT EXISTS notes
        (id int NOT NULL AUTO_INCREMENT,
        type VARCHAR(10) DEFAULT NULL,
        headimg MEDIUMTEXT NULL,
        title MEDIUMTEXT NOT NULL,
        content MEDIUMTEXT NOT NULL,
        created INT(11) NOT NULL,
        steamid bigint(20) NOT NULL,
        PRIMARY KEY (id));",
        
        "CREATE TABLE IF NOT EXISTS global_settings
        (name VARCHAR(50) NOT NULL,
        value varchar(15000) NULL,
        PRIMARY KEY (name));",

        "CREATE TABLE IF NOT EXISTS transactions (
        id int(11) NOT NULL AUTO_INCREMENT,
        credits double NOT NULL DEFAULT 0,
        steamid bigint(20) NOT NULL,
        timestamp int(11) NOT NULL,
        PRIMARY KEY (id))"
    ];
    $connection = mysqli_connect($server,$user,$pass);
    if (!$connection){
        die("Connection failed: ".mysqli_connect_error());
    }
    $sql="CREATE DATABASE IF NOT EXISTS $dbname";
    if (mysqli_query($connection,$sql)){
        $connection=mysqli_connect($server,$user,$pass,$dbname);
        foreach($tables as $val){
            $query = $connection->query($val);
            if(!$query){
                die("Creation db failed ($connection->error)");
            }
        }
    }else{
        die("Creation db failed ($connection->error)");
    }
    return $connection;
}
$database = InitDB($settings["db"]["host"].":".$settings["db"]["port"],$settings["db"]["username"],$settings["db"]["password"],$settings["db"]["database"]);
//all auth stuff is down here
function fetchdata($sid){
    $url = file_get_contents("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=".$GLOBALS['settings']['steam_api_key']."&steamids=".$sid); 
	return json_decode($url,true);
}
function getSteamData($sid){
    if (!isset($sid)){return false;}
    $trybd=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$sid';")->fetch_assoc();
    if($trybd){
        return $trybd;
    }else{
	    $data = fetchdata($sid);
	    $upd_personanm = $GLOBALS["database"]->real_escape_string($data["response"]["players"][0]["personaname"]);
        $upd_avatar = $GLOBALS["database"]->real_escape_string($data["response"]["players"][0]["avatarfull"]);
	    $GLOBALS["database"]->query("INSERT INTO users (steamid,name,avatarfull) VALUES ('$sid','$upd_personanm','$upd_avatar')");
        return array("name"=>$upd_personanm,"avatarfull"=>$upd_avatar);
    }
}
if (!isset($_SESSION["db_updated"])&&isset($_SESSION["steamid"])){//cache steam data and update online status each 20 sec
	$data = fetchdata($_SESSION["steamid"]);
	$upd_personanm = $GLOBALS["database"]->real_escape_string($data["response"]["players"][0]["personaname"]);
    $upd_avatar = $GLOBALS["database"]->real_escape_string($data["response"]["players"][0]["avatarfull"]);
	$upd_sid=$_SESSION["steamid"];
	$database->query("INSERT INTO users (steamid,name,avatarfull,registered,last_online) VALUES ('$upd_sid','$upd_personanm','$upd_avatar',UNIX_TIMESTAMP(NOW()),UNIX_TIMESTAMP(NOW())) ON DUPLICATE KEY UPDATE avatarfull='$upd_avatar',name='$upd_personanm',last_online=UNIX_TIMESTAMP(NOW())");
    $_SESSION["db_updated"]=time();
    $_SESSION["avatarfull"]=$upd_avatar;
    $_SESSION["name"]=$upd_personanm;
    unset($upd_avatar,$upd_personanm,$upd_sid,$content,$fetch);
}elseif(isset($_SESSION["db_updated"])&&($_SESSION["db_updated"]+20<time())){
	$upd_sid=$_SESSION["steamid"];
    $database->query("UPDATE users SET last_online=UNIX_TIMESTAMP(NOW()) where steamid='$upd_sid'");
    $_SESSION["db_updated"]=time();
    unset($upd_sid);
}
?>