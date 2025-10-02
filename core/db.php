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
        ugroup int(11) DEFAULT NULL,
        PRIMARY KEY (steamid)
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS servers(
        id int(11) NOT NULL AUTO_INCREMENT,
        sv_name VARCHAR(100) NOT NULL,
        sv_ip VARCHAR(100) NOT NULL,
        sv_port INT(20) NOT NULL,
        PRIMARY KEY (id)
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS bans (
        id int(11) NOT NULL AUTO_INCREMENT,
        type VARCHAR(5) DEFAULT NULL,
        server VARCHAR(100) NOT NULL,
        offender_steamid bigint(20) NOT NULL,
        admin_steamid bigint(20) DEFAULT NULL,
        created int(11) DEFAULT NULL,
        expires int(11) DEFAULT NULL,
        reason varchar(500) DEFAULT NULL,
        PRIMARY KEY (id)
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS notes (
        id int NOT NULL AUTO_INCREMENT,
        type VARCHAR(10) DEFAULT NULL,
        headimg MEDIUMTEXT NULL,
        title MEDIUMTEXT NOT NULL,
        content MEDIUMTEXT NOT NULL,
        created INT(11) NOT NULL,
        steamid bigint(20) NOT NULL,
        PRIMARY KEY (id)
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS global_settings (
        name VARCHAR(50) NOT NULL,
        value varchar(1500) NULL,
        PRIMARY KEY (name)
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS transactions (
        id int(11) NOT NULL AUTO_INCREMENT,
        credits double NOT NULL DEFAULT 0,
        steamid bigint(20) NOT NULL,
        timestamp int(11) NOT NULL,
        PRIMARY KEY (id)
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS forum_cats (
        id int NOT NULL AUTO_INCREMENT,
        name VARCHAR(50) NOT NULL,
        prior TINYINT(4) DEFAULT NULL,
        PRIMARY KEY (id)
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS forum_subcats (
        id int NOT NULL AUTO_INCREMENT,
        cat_id int NOT NULL,
        name VARCHAR(50) NOT NULL,
        icon VARCHAR(100) DEFAULT NULL,
        prior TINYINT(4) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY fk_fc_fsc (cat_id),
        CONSTRAINT fk_fc_fsc FOREIGN KEY (cat_id) REFERENCES forum_cats(id) ON DELETE CASCADE ON UPDATE CASCADE
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS forum_threads (
        id int NOT NULL AUTO_INCREMENT,
        subcat_id int NOT NULL,
        sid BIGINT(20) NOT NULL,
        topic VARCHAR(50) NOT NULL,
        timestamp int(11) NOT NULL,
        last_posted int(11) NOT NULL,
        last_post_sid BIGINT(20),
        locked tinyint(1),
        pinned tinyint(1),
        PRIMARY KEY (id),
        KEY fk_ft_fsc (subcat_id),
        KEY fk_ft_usr (sid),
        KEY fk_ft_usr_lp (last_post_sid),
        CONSTRAINT fk_ft_fsc FOREIGN KEY (subcat_id) REFERENCES forum_subcats(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_ft_usr FOREIGN KEY (sid) REFERENCES users(steamid) ON DELETE CASCADE,
        CONSTRAINT fk_ft_usr_lp FOREIGN KEY (last_post_sid) REFERENCES users(steamid) ON DELETE CASCADE ON UPDATE CASCADE
    )ENGINE=InnoDB;",

    "CREATE TABLE IF NOT EXISTS forum_posts (
        id int NOT NULL AUTO_INCREMENT,
        thread_id int NOT NULL,
        isreplyto int(11) DEFAULT NULL,
        sid BIGINT(20) NOT NULL,
        content mediumtext NOT NULL,
        timestamp int(11) NOT NULL,
        edited int(11) DEFAULT NULL,
        PRIMARY KEY (id),
        KEY fk_fp_ft (thread_id),
        KEY fk_fp_usr (sid),
        KEY fk_fp_fp (isreplyto),
        CONSTRAINT fk_fp_fp FOREIGN KEY (isreplyto) REFERENCES forum_posts(id) ON DELETE SET NULL,
        CONSTRAINT fk_fp_ft FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT fk_fp_usr FOREIGN KEY (sid) REFERENCES users(steamid)
    )ENGINE=InnoDB;"
    ];

    $connection=mysqli_connect($server,$user,$pass);
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
        $gacc=$GLOBALS["settings"]["access_manual"];
        if (!empty($gacc)) {
            foreach ($gacc as $sid=>$group) {
                $sid=$connection->real_escape_string($sid);
                $group=(int)$group;
                $sql="INSERT INTO users (steamid, ugroup) VALUES ('$sid', $group) ON DUPLICATE KEY UPDATE ugroup = VALUES(ugroup)";
                if (!$connection->query($sql)) {
                    die("Failed to set user group: ".$connection->error);
                }
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
//
function getUserGroup($sid=null){
  $sid=$sid?:($_SESSION["steamid"]??null);
  if (!$sid) return null;
  $r=$GLOBALS["database"]->query("SELECT ugroup FROM users WHERE steamid='$sid' LIMIT 1");
  return ($r&&$row=$r->fetch_assoc())?(int)$row["ugroup"]:null;
}
function hasAccess($accval,$sid=null){
  $g=getUserGroup($sid);
  return $g && !empty($GLOBALS["settings"]["ugroups"][$g][$accval]);
}
function getRankArray($sid=null){
  $g=getUserGroup($sid);
  return $g?$GLOBALS["settings"]["ugroups"][$g]:null;
}
?>