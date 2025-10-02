<?php
session_start();
require("SteamConfig.php");
if (!isset($_SESSION["steamid"])) {
    header("Location: index.php");
    exit;
}

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Redirecting...</title>
<script>
window.location.href="<?=$steamauth["loginpage"]?>";
</script>
<noscript>
<meta http-equiv="refresh" content="0;url=<?=$steamauth["loginpage"]?>" />
</noscript>
</head>
<body>
Redirecting...
</body>
</html>