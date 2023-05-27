<?php
require_once("db.php");
require_once("steamauth/steamauth.php");

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
        $database->query("UPDATE bans SET reason='$reason',expires='$expires' where offender_steamid='$sid' AND type='$type' ORDER BY created DESC LIMIT 1");
	}else{
        $type=$_POST["type"];
        $database->query("UPDATE bans SET expires=UNIX_TIMESTAMP(NOW()) where offender_steamid='$sid' AND type='$type' ORDER BY created DESC LIMIT 1");
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
    $title=$_POST["title"];
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
    $response=$database->query("SELECT * FROM notes ORDER BY ID DESC;");
    if (mysqli_num_rows($response)){
        echo json_encode($response->fetch_all(MYSQLI_ASSOC))??"";
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