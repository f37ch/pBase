<?php
session_start();
require("SteamConfig.php");

if (!isset($_SESSION["steamid"])) {
    header("Location: index.php");
    exit;
}

$loginPage=filter_var($steamauth["loginpage"],FILTER_VALIDATE_URL)?$steamauth["loginpage"]:"/";

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
</head>
<body>
<script>
  if (window.visualViewport && window.visualViewport.scale !== 1) {
    document.querySelector('meta[name="viewport"]').setAttribute(
      'content', 
      'width=device-width, initial-scale=1, maximum-scale=1'
    );
  }
  window.location.replace("<?= addslashes($loginPage) ?>");
</script>
<noscript>
  <meta http-equiv="refresh" content="0;url=<?= htmlspecialchars($loginPage, ENT_QUOTES, 'UTF-8') ?>">
</noscript>
</body>
</html>