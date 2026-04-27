<?php
ob_start();
ini_set("session.gc_maxlifetime",1555200);
session_set_cookie_params(1555200);
session_start();
require("SteamConfig.php");

if (isset($_GET["login"])) {
    require("openid.php");
    try {
        $openid=new LightOpenID($steamauth["domainname"]);

        if (property_exists($openid,"timeout")) {
            $openid->timeout = 10;
        }

        if (!$openid->mode) {
            $openid->identity="https://steamcommunity.com/openid";
            ob_end_clean();
            header("Location: ".$openid->authUrl());
            exit;
        } elseif ($openid->mode=="cancel") {
            ob_end_clean();
            echo "User has canceled authentication!";
            exit;
        } else {
            if ($openid->validate()) {
                $id = $openid->identity;
                $ptn = "/^https?:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/";

                if (!preg_match($ptn,$id,$matches)) {
                    echo "Invalid Steam ID format.";
                    exit;
                }

                $_SESSION["steamid"]=$matches[1];
                session_write_close();

                ob_end_clean();
                header("Location: core/steamauth/postlogin.php");
                exit;
            } else {
                ob_end_clean();
                echo "User is not logged in.";
                exit;
            }
        }
    } catch (ErrorException $e) {
        ob_end_clean();
        http_response_code(500);
        error_log("SteamAuth error: ".$e->getMessage());
        echo "Authentication error. Please try again.";
        exit;
    }
}

if (isset($_GET["logout"])) {
    session_unset();
    session_destroy();
    session_write_close();
    ob_end_clean();
    header("Location: ".$steamauth["logoutpage"]);
    exit;
}

ob_end_flush();
?>