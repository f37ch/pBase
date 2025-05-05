<?php
$methods["da"]=array("img"=>"../../img/da.png");
if (isset($_GET["check"])){
	include_once("../../core/db.php");
	$svid=intval($_GET["svid"]);
	$server=$database->query("SELECT * FROM servers WHERE id='$svid';")->fetch_assoc();
	$steamid=$database->real_escape_string($_GET["steamid"]);
    $link=$server["sv_name"]=="Cinema"?"equestriateam":"ponyrp";
	header("Location: https://www.donationalerts.com/r/$link");
}elseif(isset($_GET["accept"])){
	include_once("../../core/rcon.php");
	include_once("../../core/db.php");
    $key=$_GET["key"];
	$amount=round($_GET["amount"]);
	$steamid=$_GET["steamid"];
	if ($settings["api_key"]==$key){
		$database->query("INSERT INTO transactions (credits,steamid,timestamp) VALUES ('$amount','$steamid',UNIX_TIMESTAMP(NOW()))");
		http_response_code(200);
	}else{
		http_response_code(404);
	}
}
?>