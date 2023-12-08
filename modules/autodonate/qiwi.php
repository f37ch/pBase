<?php
$methods["qiwi"]=array("img"=>"https://i.imgur.com/1vkmgBv.jpg");
const QIWI_SECRET="";//get at p2p.qiwi.com
$qiwi_piblic="";//get at p2p.qiwi.com
$qiwi_themecode="";//get at p2p.qiwi.com
$rooturl=(!empty($_SERVER["HTTPS"])?"https":"http")."://".$_SERVER["HTTP_HOST"]."/";
if (isset($_GET["check"])){
	include_once("../../core/db.php");
	require_once("../../core/qiwi_billpayments/BillPayments.php");
	require_once("../../core/qiwi_billpayments/BillPaymentsException.php");
	$amount = round($_GET["amount"]);
	$svid = $_GET["svid"];
	$server=$database->query("SELECT * FROM servers WHERE id='$svid';")->fetch_assoc();
	$steamid = $_GET["steamid"];
	$description= "Пожертвование от ".$_GET['steamid']." на сумму ".$amount." RUB. Для сервера ".$server["sv_name"].".";
	$billPayments = new Qiwi\Api\BillPayments(QIWI_SECRET);
	$params = [
		"publicKey" => $qiwi_piblic,
		"amount" => $amount,
		"billId" => $billPayments->generateId(),
		"comment" => $description,
		"successUrl" => $rooturl,
		"customFields" => array("sv_name"=>$server["sv_name"],"steamid"=>$steamid,"themeCode"=>$qiwi_themecode)
	];
	header("Location: ".$billPayments->createPaymentForm($params));
}elseif(isset($_GET["accept"])){///////////
	include_once("../../core/rcon.php");
	include_once("../../core/db.php");
	require_once("../../core/qiwi_billpayments/BillPayments.php");
	require_once("../../core/qiwi_billpayments/BillPaymentsException.php");
	$json = file_get_contents("php://input");
	$decoded = json_decode($json,true);
	$sv_name = $decoded["bill"]["customFields"]["sv_name"];
	$steamid = $decoded["bill"]["customFields"]["steamid"];
	$amount = round($decoded["bill"]["amount"]["value"]);
	$status = $decoded["bill"]["status"]["value"];
	$head = array_change_key_case(getallheaders(),CASE_LOWER);
	$validSignatureFromNotificationServer = $head[mb_strtolower("x-api-signature-SHA256")];
	if ($billPayments->checkNotificationSignature($validSignatureFromNotificationServer,$decoded,QIWI_SECRET)){
		$row=$database->query("SELECT * FROM servers WHERE sv_name='$sv_name';")->fetch_assoc();
		$database->query("INSERT INTO transactions (credits,steamid,timestamp) VALUES ('$amount','$steamid',UNIX_TIMESTAMP(NOW()))");
		if ($row){
			Rcon($row["sv_ip"],$row["sv_port"],'es_setbalance "'.$steamid.'" "'.$amount.'"');
		}
		http_response_code(200);
	}else{
		http_response_code(404);
	}
}
?>