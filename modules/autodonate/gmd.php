<?php
$methods["gmd"]=array("img"=>"https://i.imgur.com/KRvcJt1.png");
if (isset($_GET["check"])){
	include_once("../../core/db.php");
	$amount=intval($_GET["amount"]);// "1.50"
	$svid=intval($_GET["svid"]);
	$server=$database->query("SELECT * FROM servers WHERE id='$svid';")->fetch_assoc();
	$steamid=$database->real_escape_string($_GET["steamid"]);
    $svid=$server["sv_name"]=="Cinema"?1424:1785;//your project ids from gmd panel
	$projectSecret=$svid==1424?"cinemakey":"rpkey";//your project secret keys
	$params=[
	    "sid"=>$steamid,
	    "sum"=>$amount
	];
	$options=[
	    "http"=>[
	        "header" => [
	            "sign: ".hash("sha256",$steamid."{up}".$amount."{up}".$projectSecret),
	            "project: ".$svid,
	            "User-Agent: Valve/Steam HTTP Client 1.0 (4000)"
	        ],
	        "method"=>"POST",
	        "content"=>http_build_query($params),
	    ],
	];
	$context=stream_context_create($options);
	$result=file_get_contents("https://gm-donate.net/api/url/getPayment",false,$context);
	if ($result===FALSE) {
	    die('Error occurred');
	}
	$ara=json_decode($result,true);
	header("Location: ".$ara["data"]);
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