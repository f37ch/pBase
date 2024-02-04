<?php
require_once("db.php");
require_once("steamauth/steamauth.php");

//Get donations data
if (isset($_GET["donations"]))
{
    if ($settings["api_key"]!=$_GET["api_key"]){
        http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
    }
    $topsupp=$database->query("SELECT steamid,SUM(credits) FROM transactions GROUP BY steamid ORDER BY SUM(credits) DESC LIMIT 10");
    $recentdon=$database->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 10");
    $mgoal=$database->query("SELECT value FROM global_settings WHERE name = 'donate_goal'")->fetch_row()[0]??"5000";
    $collected=$database->query("SELECT SUM(credits) FROM transactions where FROM_UNIXTIME(timestamp,'%m %y') = FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()),'%m %y')")->fetch_row()[0]??"0";
    $table=array("top"=>array(),"recent"=>array(),"goal"=>$mgoal,"collected"=>$collected);
    $cnt=0;
    while ($row=$topsupp->fetch_assoc()) {
        $user=getSteamData($row["steamid"]);
        $table["top"][$cnt]=array($user["name"],$row["SUM(credits)"],$row["steamid"],$user["avatarfull"]);
        $cnt++;
    }
    $cnt=0;
    while ($row=$recentdon->fetch_assoc()){
        $user=getSteamData($row["steamid"]);
        $table["recent"][$cnt]=array($user["name"],$row["credits"],$row["steamid"],$user["avatarfull"]);
        $cnt++;
    }
    echo json_encode($table);
}

//Synch bans and activity
if (isset($_GET["synch_user"]))
{
    if ($settings["api_key"]!=$_GET["api_key"]){
        http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
    }
    $sid=$_GET["synch_user"];
    getSteamData($sid);
    $database->query("UPDATE users SET last_played=UNIX_TIMESTAMP(NOW()) where steamid='$sid'");
    echo json_encode(array("success"=>"User synched successfully."));
}

if (isset($_GET["synch_ban"]))
{
    if ($settings["api_key"]!=$_GET["api_key"]){
        http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
    }
    
    $sid=$_GET["synch_ban"];
    if (!isset($_POST["edited"])) {
        $admin=$_POST["admin"]??NULL;
        $type=$_POST["type"];
        $server=$_POST["server"];
        $reason=$_POST["reason"];
        $expires=$_POST["expires"];
        $database->query("INSERT INTO bans (type,offender_steamid,server,reason".(isset($admin)?",admin_steamid":"").",created".($expires>0?",expires":"").") VALUES ('$type','$sid','$server','$reason',".(isset($admin)?"'$admin',":"")."UNIX_TIMESTAMP(NOW())".($expires>0?",'$expires'":"").")");
	}elseif(!isset($_POST["unban"])){
        $reason=$_POST["reason"];
        $expires=$_POST["expires"];
        $type=$_POST["type"];
        $server=$_POST["server"];
        $database->query("UPDATE bans SET reason='$reason',expires='$expires' where offender_steamid='$sid' AND type='$type' AND server='$server' ORDER BY created DESC LIMIT 1");
	}else{
        $type=$_POST["type"];
        $server=$_POST["server"];
        $database->query("UPDATE bans SET expires=UNIX_TIMESTAMP(NOW()) where offender_steamid='$sid' AND type='$type' AND server='$server' ORDER BY created DESC LIMIT 1");
	}
    echo json_encode(array("success"=>"Ban synched successfully."));
}

//Notes
if (isset($_POST["get_tinydata"]))
{
    if (!isset($settings["access"][$_SESSION["steamid"]]["notes"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $id=$_POST["get_tinydata"];
    $response=$database->query("SELECT * FROM notes WHERE id='$id'");
    if (mysqli_num_rows($response)){
        echo json_encode($response->fetch_array(MYSQLI_ASSOC))??"";
    };
}
if (isset($_POST["write_save"]))
{
    if (!isset($settings["access"][$_SESSION["steamid"]]["notes"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $type=$_POST["write_save"];
    $headimg=$_POST['headimg']??NULL;
    $content=base64_encode($_POST["content"]);
    $title=$_POST["title"];
    $sid=$_SESSION["steamid"];
    $sql=$database->query("INSERT INTO notes (type,headimg,title,content,created,steamid) VALUES ('$type','$headimg','$title','$content',UNIX_TIMESTAMP(NOW()),'$sid')");
    if (!$sql){
        echo json_encode(array("error"=>"Ошибка: ".mysqli_error($database)));
    }else{
        echo json_encode(array("success"=>"Запись добавлена!"));
    };
}
if (isset($_POST["write_update"]))
{
    if (!isset($settings["access"][$_SESSION["steamid"]]["notes"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $id=$_POST["write_update"];
    $headimg=$_POST['headimg']??NULL;
    $content=base64_encode($_POST["content"]);
    $title=empty($_POST["title"])?"title":$_POST["title"];
    $sql=$database->query("UPDATE notes SET headimg='$headimg',title='$title',content='$content' WHERE id='$id'");
    if (!$sql){
        echo json_encode(array("error"=>"Ошибка: ".mysqli_error($database)));
    }else{
        echo json_encode(array("success"=>"Запись обновлена!"));
    };
}
if (isset($_POST["get_notes"]))
{
    if (!isset($settings["access"][$_SESSION["steamid"]]["notes"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $page=is_numeric($_POST["get_notes"])?$_POST["get_notes"]:1;
    $limit=5;
    $start=($page-1)*$limit;
    $countres=$database->query("SELECT count(id) AS id FROM notes")??NULL;
    $fetchedcount=$countres->fetch_all(MYSQLI_ASSOC);
    $total=$fetchedcount[0]["id"];
    $pages=ceil($total/$limit);
    $prev=$page>1?$page-1:1;
	$nxt=$page!=$pages?$page+1:$pages;
    $response=$database->query("SELECT * FROM notes ORDER BY ID DESC LIMIT $start, $limit;");
    if (mysqli_num_rows($response)){
        echo json_encode(array("page"=>$page,"pages"=>$pages,"prev"=>$prev,"next"=>$nxt,"data"=>$response->fetch_all(MYSQLI_ASSOC))??"");
    };
}
if(isset($_POST["nrm"])){
    if (!isset($settings["access"][$_SESSION["steamid"]]["notes"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $id=$_POST["nrm"];
    $sql=$database->query("DELETE FROM notes WHERE id='$id'");
    if (!$sql){
        echo json_encode(array("error"=>"Ошибка: ".mysqli_error($database)));
    }else{
        echo json_encode(array("success"=>"Успешное удаление!"));
    };
}

//Globals
if (isset($_POST["settings_infoget"]))
{
    if (!isset($settings["access"][$_SESSION["steamid"]]["global"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $id=$_POST["settings_infoget"];
    $response=$database->query("SELECT value FROM global_settings WHERE name = '$id';");
    if (mysqli_num_rows($response)){
        echo $response->fetch_array()[0]??"";
    };
}
if(isset($_POST["settings_insert"])){
    if (!isset($settings["access"][$_SESSION["steamid"]]["global"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $name=$_POST["settings_insert"];
    $value=$_POST["value"];
    $response=$database->query("INSERT INTO global_settings (name,value) VALUES ('$name','$value') ON DUPLICATE KEY UPDATE value = '$value';");
}

//Servers
if (isset($_POST["get_servers"]))
{
    $response=$database->query("SELECT * FROM servers;");
    if (mysqli_num_rows($response)){
        echo json_encode($response->fetch_all(MYSQLI_ASSOC))??"";
    };
}
if(isset($_POST["svrm"])){
    if (!isset($settings["access"][$_SESSION["steamid"]]["servers"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $name=$_POST["svrm"];
    $sql=$database->query("DELETE FROM servers WHERE sv_name='$name'");
}
if (isset($_POST["svsave"]))
{
    if (!isset($settings["access"][$_SESSION["steamid"]]["servers"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $name=$_POST["name"];
    $ip=$_POST["ip"];
    $port=$_POST["port"];
    $response=$database->query("INSERT INTO servers (sv_name,sv_ip,sv_port) VALUES ('$name','$ip','$port') ON DUPLICATE KEY UPDATE sv_name = '$name', sv_ip = '$ip', sv_port = '$port';");
}
?>