<?php
$methods["gmd"]=array("img"=>"https://i.imgur.com/KRvcJt1.png");
if (isset($_GET["check"])){
	include_once("../../core/db.php");
	$amount=number_format($_GET["amount"],2,".","");// "1.50"
	$steamid=$_GET["steamid"];
    $svid=$_GET["svid"]==1?1424:1785;//your project ids from gmd panel
	$projectSecret="";//your project key from gmd panel
	$params=[
        "sid"=>$steamid,
        "sum"=>$amount
    ];
    $headers=[
        "sign:".hash("sha256",$steamid."{up}".$amount."{up}".$projectSecret),
        "project:".$svid
    ];
    $ch=curl_init("https://gm-donate.net/api/url/getPayment");
    curl_setopt($ch,CURLOPT_IPRESOLVE,CURL_IPRESOLVE_V4);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data=curl_exec($ch);
    curl_close($ch);
    $ara=json_decode($data,true);
	header("Location: ".$ara["data"]);
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