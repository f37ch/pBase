<?php
//Version 4.0
$rooturl=(!empty($_SERVER["HTTPS"])?"https":"http")."://".$_SERVER["HTTP_HOST"]."/";
$steamauth['domainname'] = $rooturl; // The main URL of your website displayed in the login page
$steamauth['logoutpage'] = $rooturl; // Page to redirect to after a successfull logout
$steamauth['loginpage'] = $rooturl."profile.php"; // Page to redirect to after a successfull login

// System stuff
if (empty($steamauth['domainname'])) {$steamauth['domainname'] = $_SERVER['SERVER_NAME'];}
if (empty($steamauth['logoutpage'])) {$steamauth['logoutpage'] = $_SERVER['PHP_SELF'];}
if (empty($steamauth['loginpage'])) {$steamauth['loginpage'] = $_SERVER['PHP_SELF'];}
?>
