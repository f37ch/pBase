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
  function formatsize($size){
      if ($size<=1000000){
          $size=number_format($size/1000,2)." KB";
      }elseif($size<=1000000000) {
          $size=number_format($size/1000000,2)." MB";
      }elseif ($size<=1000000000000){
          $size=number_format($size/1000000000,2)." GB";
      }else{
          $size=number_format($size/1000000000000,2)." TB";
      }
      return $size;
  }
  function plural($n,$a,$b,$c){
    switch($n%10==1&&$n%100!=11?0:($n%10>=2&&$n%10<=4&&($n%100<10 or $n%100>=20)?1:2)){
      case 0:default:return $a;
      case 1:return $b;
      case 2:return $c;
    }
  }
  function elapsed($when)
  {
    $rtime=time()-$when;
    if ($rtime<=1){return "только что";}
    $a=array(365*24*60*60=>"год",30*24*60*60=>"месяц",24*60*60=>"день",60*60=>"час",60 =>"минута",1=>"секунда");
    $a_da=array("год"=>["год","года","лет"],"месяц"=>["месяц","месяца","месяцев"],"день"=>["день","дня","дней"],"час"=>["час","часа","часов"],"минута"=>["минута","минуты","мин"],"секунда"=>["секунда","секунды","секунд"]);
    foreach ($a as $si=>$str)
    {
      $d=$rtime/$si;
      if ($d>1)
      {
        $r=round($d);
        return $r." ".plural($r,$a_da[$str][0],$a_da[$str][1],$a_da[$str][2])." назад";
      }
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