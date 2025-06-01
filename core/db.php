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
$database=InitDB($settings["db"]["host"].":".$settings["db"]["port"],$settings["db"]["username"],$settings["db"]["password"],$settings["db"]["database"]);
//all auth stuff is down here
function fetchdata($sid){
    $json=@file_get_contents("https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=".$GLOBALS["settings"]["steam_api_key"]."&steamids=".$sid); 
    if (!$json){
        return null;
    }
    return json_decode($json,true);
}
function getSteamData($sid,$forsenew=false){
    if (!$sid) return false;

    $existing=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$sid';")->fetch_assoc();

    if (!$forsenew){
        if ($existing){
            return $existing;
        }
    }
    
    $data=fetchdata($sid);
    if ($data&&!empty($data["response"]["players"][0])) {
        $player=$data["response"]["players"][0];
        $upd_personanm=$GLOBALS["database"]->real_escape_string($player["personaname"]);
        $upd_avatar=$GLOBALS["database"]->real_escape_string($player["avatarfull"]);
    } elseif(!$existing){
        $fallback_name="User";
        $fallback_avatar="https://avatars.steamstatic.com/b5bd56c1aa4644a474a2e4972be27ef9e82e517e_full.jpg";
        $upd_personanm=$GLOBALS["database"]->real_escape_string($fallback_name);
        $upd_avatar=$GLOBALS["database"]->real_escape_string($fallback_avatar);
    } else {
        return $existing;
    }
    $GLOBALS["database"]->query("INSERT INTO users (steamid, name, avatarfull) VALUES ('$sid','$upd_personanm','$upd_avatar') ON DUPLICATE KEY UPDATE name='$upd_personanm', avatarfull='$upd_avatar'");
    return array("name"=>$upd_personanm,"avatarfull"=>$upd_avatar);
}
function toCommunityID($id) {
    if (preg_match("/^STEAM_/",$id)) {
        $parts = explode(":",$id);
        return bcadd(bcadd(bcmul($parts[2],"2"),"76561197960265728"),$parts[1]);
    } elseif (is_numeric($id) && strlen($id) < 16) {
        return bcadd($id,"76561197960265728");
    } else {
        return $id;
    }
}
if (!isset($_SESSION["db_updated"])&&isset($_SESSION["steamid"])){//cache steam data and update online status each 20 sec
	$data=getSteamData($_SESSION["steamid"],true);
	$upd_personanm=$GLOBALS["database"]->real_escape_string($data["name"]);
    $upd_avatar=$GLOBALS["database"]->real_escape_string($data["avatarfull"]);
	$upd_sid=$_SESSION["steamid"];
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