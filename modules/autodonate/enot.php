<?php
$methods["enot"]=array("img"=>"https://i.imgur.com/Hz6BjfA.jpg");
$enot_shopid="";//get at enot.io
$enot_secret="";//get at enot.io
$enot_secret2="";//get at enot.io
if (isset($_GET["check"])){
    include_once("../../core/db.php");
    $amount=intval($_GET["amount"]);
    $svid=intval($_GET["svid"]);
	$server=$database->query("SELECT * FROM servers WHERE id='$svid';")->fetch_assoc();
    $steamid=$database->real_escape_string($_GET["steamid"]);
    $description="Покупка внутриигровой валюты от ".$_GET['steamid']." на сумму ".$amount." RUB. Для сервера ".$server["sv_name"].".";
    $params=[
        "shop_id"=>$enot_shopid,
        "amount"=>$amount,
        "currency"=>"RUB",// or "USD", "EUR"
        "comment"=> $description,
        "order_id"=>time(),
        "custom_fields"=>json_encode(array("sv_name"=>$server["sv_name"],"steamid"=>$steamid))
    ];
    $headers=[
        "Accept: application/json",
        "Content-Type: application/json",
        "x-api-key: $enot_secret"
    ];
    $ch=curl_init("https://api.mivion.com/invoice/create/?".http_build_query($params));
    curl_setopt($ch,CURLOPT_IPRESOLVE,CURL_IPRESOLVE_V4);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch,CURLOPT_POST,1);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$params);
    curl_setopt($ch,CURLOPT_HTTPHEADER,$headers);
    $data=curl_exec($ch);
    curl_close($ch);
    $ara=json_decode($data,true);

    header("Location: ".$ara["data"]["url"]);
}elseif(isset($_GET["accept"])){///////////
    include_once("../../core/db.php");
    include_once("../../core/rcon.php");
    $json=file_get_contents("php://input");
    $decoded=json_decode($json,true);
    ksort($decoded);
    $sv_name=$decoded["custom_fields"]["sv_name"];
    $steamid=$decoded["custom_fields"]["steamid"];
    $amount=round($decoded["amount"]);
    $status=$decoded["status"];

    $signature_header=$_SERVER["HTTP_X_API_SHA256_SIGNATURE"]??"";
    $sign=json_encode($decoded);
    $calculated_hmac=hash_hmac("sha256",$sign,$enot_secret2);
    if ($status=="success"&&hash_equals($signature_header,$calculated_hmac)){
        $row=$database->query("SELECT * FROM servers WHERE sv_name='$sv_name';")->fetch_assoc();
    	$database->query("INSERT INTO transactions (credits,steamid,timestamp) VALUES ('$amount','$steamid',UNIX_TIMESTAMP(NOW()))");
    	if ($row){
    		Rcon($row["sv_ip"],$row["sv_port"],'es_setbalance "'.$steamid.'" "'.$amount.'"');
    	}
    	http_response_code(200);
    }else{
        //file_put_contents("../enot_error.log",date("Y-m-d H:i:s")." - callback # enot: Invalid signature."."\r\n".$json."\r\n".$signature_header,FILE_APPEND);
        http_response_code(404);
    }
}
?>