<?php
  require_once(__DIR__."/../core/config.php");
  if ($settings["dev_mode"]){
    ini_set("display_errors",1); 
    ini_set("display_startup_errors",1);
  }else{
    ini_set("display_errors",0); 
    ini_set("display_startup_errors",0);
  }
  require_once(__DIR__."/../core/steamauth/steamauth.php");
  require_once(__DIR__."/../core/db.php");
  function getPage($nav)
  {
    if (isset($_GET["page"])&&$_GET["page"]==$nav||!isset($_GET["page"])&&$nav=="home")
    {
      return "nav-link active";
    }else{
      return "nav-link";
    }
  }
  function getSetting($id,$bool)
  {
    $response=$GLOBALS['database']->query("SELECT value FROM global_settings WHERE name = '$id';");
    if (!mysqli_num_rows($response)){
        return NULL;
    }else{
        if ($bool){
          return $response->fetch_array()[0]=="true"?true:false;
        }else{
          return $response->fetch_array()[0]??NULL;
        }
    };
  }
  function asset_version($path) 
  {
    $fullPath=$_SERVER["DOCUMENT_ROOT"].$path;
    if (file_exists($fullPath)){
        return $path."?v=".filemtime($fullPath);
    } else {
        return $path;
    }
  }
?>
<!DOCTYPE HTML>
<html lang="ru" class="h-100">
<head>
  <title><?php echo getSetting("project_name",false)??"pBase"?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="Octaver">
  <link href="<?=asset_version("/css/bootstrap_icons/bootstrap-icons.css")?>" rel="stylesheet" >
  <link href="<?=asset_version("/css/bootstrap.css")?>" rel="stylesheet">
  <link href="<?=asset_version("/css/aos.css")?>" rel="stylesheet">
  <link href="<?=asset_version("/css/pbase.min.css")?>"  rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="<?=getSetting("favicon",false)??"../favicon.ico"?>">
</head>

<body class="d-flex text-center text-white bg-dark" style="<?php echo "background-image:linear-gradient(-45deg, ".getSetting("bg_color",false).")"??""?>">
<div class="pbase-container d-flex w-100 p-3 mx-auto flex-column">