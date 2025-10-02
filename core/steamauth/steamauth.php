<?php
ob_start();
session_start();
require("SteamConfig.php");

if (isset($_GET["login"])){
    require("openid.php");
    try {
        $openid=new LightOpenID($steamauth["domainname"]);

        if (!$openid->mode){
            $openid->identity="https://steamcommunity.com/openid";
            header("Location: ".$openid->authUrl());
            exit;
        } elseif ($openid->mode=="cancel") {
            echo "User has canceled authentication!";
            exit;
        } else {
            if ($openid->validate()) {
                $id=$openid->identity;
                $ptn="/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";
                preg_match($ptn,$id,$matches);
                $_SESSION["steamid"]=$matches[1];

                session_write_close();

                header("Location: core/steamauth/postlogin.php");
                exit;
            } else {
                echo "User is not logged in.";
                exit;
            }
        }
    } catch (ErrorException $e){
        echo $e->getMessage();
        exit;
    }
}

if (isset($_GET["logout"])){
    session_unset();
    session_destroy();
    session_write_close();
    header("Location: " . $steamauth["logoutpage"]);
    exit;
}
?>