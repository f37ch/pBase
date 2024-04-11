<?php
include("head.php");
$_GET["page"]="methods";
foreach (glob("../modules/autodonate/*.php") as $file)//search autodonate modules
{
    include_once($file);
}
if (!isset($_GET["sid"])){ ?>
<div class="container mt-auto mb-auto">
    <h1 class="text-danger" data-aos="zoom-in" data-aos-delay="100">ОШИБКА</h1>
    <p class="lead" data-aos="fade-down" data-aos-delay="400">Требуется вход!</p>
    <p class="lead">
    <a class="btn mt-3 position-relative fixed-bottom btn-sm btn-secondary fw-bold border-white bg-white" data-aos="zoom-in-up" data-aos-delay="500" data-aos-offset="0" href="?login">Войдите через
    <i class="bi bi-steam"></i></a>
</div>
<?php }else{ ?>
<div class="container mt-auto mb-auto" data-aos="zoom-out" data-aos-delay="100">
<h1 class="font_big mb-4" data-aos="flip-left" data-aos-delay="200">ПЛАТЁЖНЫЕ СИСТЕМЫ</h1>
    <div class="row col-8 mx-auto d-block">
        <?php if (isset($methods)) {?>
        <?php foreach ($methods as $key=>$method){?>
            <div class="p-2 bggrad card hoverscale bg-dark" style="">
                <a target="_blank" href="<?php echo (isset($_SERVER["HTTPS"])?"https://":"http://").$_SERVER["SERVER_NAME"]."/modules/autodonate/".$key.".php?check&amount=".$_GET["amount"]."&svid=".$_GET["svid"]."&steamid=".$_GET["sid"]; ?>" referrerpolicy="origin"><img class="card-img" src="<?php echo $method["img"]?>">
            </div>
        <?php } ?>
        <?php }else{ ?>
            <h4 data-aos="zoom-out" data-aos-delay="200" class="ql-align-center text-danger">Autodonate modules not found.</h4>
        <?php } ?>
    </div>
    <!--<a href="https://send.monobank.ua/3hSmwVUvKg" class="text-white row justify-content-center"> 
        <h6 class="ql-align-center">Если по каким-то причинам Вы не можете воспользоваться платёжными системами выше — Вы можете попробовать через украинский банк, указав в комментарии платежа свой STEAMID. Сумму зачислим в течение дня, а комиссия будет минимальной.</h6>
    </a>!-->
</div>
<?php } ?>
<script src="../js/aos.js"></script>
<script type="text/javascript">
  AOS.init();
</script>