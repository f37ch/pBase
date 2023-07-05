<?php
$methods["gmd"]=array("img"=>"https://i.imgur.com/KRvcJt1.png");
if (isset($_GET["check"])){
	include_once("../../core/db.php");
	$amount = number_format($_GET["amount"],2,".","");// "1.50"
	$steamid = $_GET["steamid"];
    $svid=$_GET["svid"]==1?1424:1785;//your project ids from gmd admin panel
	$params = [
		"steamid"=>$steamid,
		"sum"=>$amount
	];
	header("Location: https://gm-donate.net/donate/".$svid."?".http_build_query($params));
}elseif(isset($_GET["accept"])){
	include_once("../../core/rcon.php");
	include_once("../../core/db.php");
    $key=$_GET["key"];
	$amount=$_GET["amount"];
	$steamid=$_GET["steamid"];
	if ($settings["api_key"]==$key){
		$database->query("INSERT INTO transactions (credits,steamid,timestamp) VALUES ('$amount','$steamid',UNIX_TIMESTAMP(NOW()))");
		http_response_code(200);
	}else{
		http_response_code(404);
	}
}
?>