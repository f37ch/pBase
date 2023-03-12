<?php
$methods["enot"]=array("img"=>"https://i.imgur.com/Hz6BjfA.jpg");
$enot_shopid="";//get at enot.io
$enot_secret="";//get at enot.io
$enot_secret2="";//get at enot.io
if (isset($_GET["check"])){
    include_once("../../core/db.php");
    include_once("../../core/rcon.php");
    $amount = number_format($_GET["amount"],2,".","");// "2.50"
    $svid = $_GET["svid"];
	$server=$database->query("SELECT * FROM servers WHERE id='$svid';")->fetch_assoc();
    $steamid = $_GET["steamid"];
    $sign = md5($enot_shopid.':'.$amount.':'.$enot_secret.':'.time());
    $description= "Пожертвование от ".$_GET['steamid']." на сумму ".$amount." RUB. Для сервера ".$server["sv_name"].".";
    $params = [
        "m" => $enot_shopid,
        "oa" => $amount,
        "cr" => "RUB",// or "USD", "EUR"
        "c" => $description,
        "o" => time(),
        "s" => $sign,
        "cf" => ["sv_name"=>$svid,"steamid"=>$steamid]
    ];
    $redirectUrl = "https://enot.io/pay/?".http_build_query($params);
    header("Location: ".$redirectUrl);
}elseif(isset($_GET["accept"])){///////////
    $sv_name = $_REQUEST["custom_field"]["sv_name"];
    $steamid = $_REQUEST["custom_field"]["steamid"];
    $amount = $_REQUEST["amount"];
    $steamid64 = toCommunityID($steamid);
    $sign = md5($_REQUEST["merchant"].":".$_REQUEST["amount"].":".$enot_secret2.":".$_REQUEST["merchant_id"]);
    if ($sign == $_REQUEST["sign_2"]){
        $row=$database->query("SELECT * FROM servers WHERE sv_name='$sv_name';")->fetch_assoc();
    	$database->query("INSERT INTO transactions (credits,steamid,timestamp) VALUES ('$amount','$steamid64',UNIX_TIMESTAMP(NOW()))");
    	if ($row){
    		Rcon($row["sv_ip"],$row["sv_port"],'es_setbalance "'.$steamid.'" "'.$amount.'"');
    	}
    	http_response_code(200);
    }else{
        http_response_code(404);
    }
}
?>