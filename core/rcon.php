<?php
require_once("db.php");
require_once("cache.php");
require_once("SourceQuery/bootstrap.php");
require_once("steamauth/steamauth.php");
use SourceQuery\SourceQuery;
function queryServer ($ip,$queryport) {
	$query = new SourceQuery();
	$server = null;
	try{
		$query->Connect($ip,$queryport,1,SourceQuery :: SOURCE);
		$data = $query->GetInfo();
	}catch(\Exception $e){
		return($server);
	}
	$query->Disconnect();
	if (is_array($data)) {
		  $server = array();
		  $server['name'] = $data["HostName"];
		  $server['map'] = $data["Map"];
		  $server['game'] = $data["ModDir"];
		  $server['description'] = $data["ModDesc"];
		  $server['players'] = $data["Players"];
		  $server['playersmax'] = $data["MaxPlayers"];
		  $server['password'] = $data["Password"];
		  $server['vac'] = $data["Secure"];
	  }
	return($server);
}
function Rcon ($ip,$queryport,$cmd) {
	$query = new SourceQuery();
	try{
		$query->Connect($ip,$queryport,1,SourceQuery :: SOURCE);
		$query->SetRconPassword($GLOBALS["settings"]["rcon"]);
		$data = $query->Rcon($cmd);
	}catch(\Exception $e){
		return($e);
	}
	$query->Disconnect();
	return($data);
}
function getplayers($ip,$port) {
	$query = new SourceQuery();
	$pdata = [];
	try
	{
		$query->Connect($ip,$port,1,SourceQuery :: SOURCE);
		$pdata = $query->GetPlayers();
	}
	catch(Exception $e)
	{
		$pdata = $e;
	}
	finally
	{
		$query->Disconnect();
	}
	$query->Disconnect();
	return $pdata??null;
}
if (isset($_POST["get_players"]))
{
    $svname=$_POST["get_players"];
	$cached=Cache::get("players_".$svname);
	if ($cached){
		echo json_encode($cached);
	}else{
    	$response=$database->query("SELECT sv_ip,sv_port FROM servers WHERE sv_name = '$svname';");
    	if (!mysqli_num_rows($response)){
    	    echo json_encode(array("error"=>"Non project server."));
    	}else{
    	    $ara=$response->fetch_array();
			$players=getplayers($ara[0],$ara[1]);
			Cache::put("players_".$svname,$players);
    	    echo json_encode(getplayers($ara[0],$ara[1]))??"";
    	};
	}
}
if (isset($_POST["get_servers"]))
{	
	$cached=Cache::get("svStatus");
	if ($cached){
		echo json_encode($cached);
	}else{
    	$response=$database->query("SELECT * FROM servers");
    	if (!mysqli_num_rows($response)){
    	    echo "";
    	}else{
			$cnt = 0;
			$data=array();
			while ($row=$response->fetch_assoc()) {
				$data[$cnt]=$row;
				$data[$cnt]["query"]=queryServer($row["sv_ip"],$row["sv_port"]);
				$cnt++;
			}
			Cache::put("svStatus",$data);
    	    echo json_encode($data)??"";
    	};
	}
}
if (isset($_POST["rcon_submit"]))
{
	if (!isset($settings["access"][$_SESSION["steamid"]]["rcon"])){
		http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
	}
    $svid=$_POST["rcon_submit"];
	$command=$_POST["command"];
	$response=$database->query("SELECT * FROM servers WHERE id='$svid'");
	
    if (!mysqli_num_rows($response)){
        echo json_encode(array("error"=>"Error: Server not found."));
    }else{
		$sv=$response->fetch_array(MYSQLI_ASSOC);
		$rcon=Rcon($sv["sv_ip"],$sv["sv_port"],$command);
        echo json_encode(array("success"=>$rcon));
    };
}