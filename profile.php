<?php 
include("components/head.php");
if (isset($_SESSION["steamid"])){
    $_GET["page"]="profile";
    $sid=$_SESSION["steamid"];
    $userdata=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$sid';")->fetch_assoc();
    unset($sid);
  }else{
    $_GET["page"]="profile";
    $_GET["error"]="Требуется вход!";
}
function plural($n,$a,$b,$c){
  switch($n%10==1&&$n%100!=11?0:($n%10>=2&&$n%10<=4&&($n%100<10or$n%100>=20)?1:2)){
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
  $a_da=array("год"=>["год","года","лет"],"месяц"=>["месяц","месяца","месяцев"],"день"=>["день","дня","дней"],"час"=>["час","часа","часов"],"минута"=>["минута","минуты","минут"],"секунда"=>["секунда","секунды","секунд"]);
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
<?php include("components/header.php") ?>
<?php if (!isset($_GET["error"])){ ?>
  <?php if (isset($settings["access"][$_SESSION["steamid"]]["notes"])){ ?>
    <script src="https://cdn.tiny.cloud/1/<?=$settings["tinymce_apikey"]?>/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>tinymce.init({selector:'textarea',image_advtab:true,plugins:'preview importcss searchreplace autolink autosave save directionality  code visualblocks visualchars fullscreen image link media template codesample table charmap pagebreak nonbreaking anchor  insertdatetime   advlist lists wordcount help charmap emoticons',resize:false,branding:false});</script>
    <div class="modal fade text-black" id="write_modal" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="staticBackdropLabel"  aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
          <div class="modal-header text-center">
            <h1 class="modal-title w-100 fs-5" id="modallbl">meg4typ3r 3000</h1>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" >
          <div class="input-group mb-3" id="iittl">
            <span class="input-group-text"><i class="bi bi-fonts"></i></span>
            <input type="text" id="iinpttl" class="shadow-none form-control" placeholder="title" aria-describedby="inpttl">
          </div>
          <div class="input-group mb-3" id="iiimg">
            <span class="input-group-text"><i class="bi bi-card-image"></i></span>
            <input type="text" id="iinpimg" class="shadow-none form-control" placeholder="headimg" aria-describedby="inpimg">
          </div>
          <textarea id="tiny"></textarea>
          </div>
          <div class="modal-footer">
            <button type="button" id="cancel" class="btn btn-danger" data-bs-dismiss="modal">Отмена</button>
            <button type="button" id="publish" class="btn btn-success">Опубликовать</button>
          </div>
        </div>
      </div>
    </div>
  <?php } ?>
  <div class="card mb-4 text-black" style="border-radius:25px; margin-top: 5%;" data-aos="fade-down" data-aos-delay="100">
    <div class="card-body">
      <div class="row p-2 text-center justify-content-center d-flex align-items-center mb-2" style="margin: -10%;">
      <div class="col-auto">
        <img class=" col-auto rounded-circle mb-3" style="border: 4px solid #000;"src="<?php echo $_SESSION["avatarfull"] ?>">
      </div> 
  <div class="col-auto">
    <h1 class="title my-0"><?php echo $_SESSION["name"]?></h1> 
  <h6 class="title" style="color: rgb(94, 197, 130);"><i class=" mr-1"></i><?=$settings["ranks"][$_SESSION["steamid"]]??"User";?></h6>
  </div> <div class="col-auto">
  <div class="row justify-content-center mt-3">
    <div class="col-auto"><div class="input-group mb-3">
      <div class="input-group-prepend">
        <a target="_blank" href="https://steamcommunity.com/profiles/<?=$_SESSION["steamid"]?>" class="btn btn-secondary fw-bold bg-white"><i class="bi bi-steam"></i> Профиль</a></div> <input onclick="this.select()" value="<?=$_SESSION["steamid"]?>" readonly="readonly" class="form-control shadow-none border-custom" style="text-align: center;">
      </div>
      <a class="btn w-100 btn-danger fw-bold btn-success btn-sm col-2" href="?logout">Разлогиниться</a>
    </div>
  </div>
  </div>
</div>
</div>
  </div>
  <div class="card mb-4" data-aos="flip-left" data-aos-delay="100">
  <div class="card-header text-black fw-bold">
    Ваше Хранилище
  </div>
  <div class="card-body">
  <form class="mb-3 input-group" action="core/db.php" method="post" enctype="multipart/form-data" id="fileform">
      <input class="form-control shadow-none" type="file" name="file" id="file" required>
      <button class="btn btn-outline-secondary" name="file_submit" type="submit"><i class="bi bi-cloud-arrow-up"></i> Загрузить</button>
  </form>
  <div class="d-none d-flex align-items-center column-gap-2 mb-1" id="uploadinf">
  <span class="text-black" id="filesize"></span>
  <span class="text-black" id="aviable"></span>
  <div class="col">
    <div class="progress" role="progressbar">
      <div class="progress-bar progress-bar-striped progress-bar-animated" id="progress" style="width: 0%"></div>
    </div>
  </div>
  </div>

  <div id="alertplace"></div>

  <div class="accordion accordion-flush" id="accordionFlushExample">
  <div class="accordion-item">
    <h2 class="accordion-header" id="flush-headingOne">
      <button class="accordion-button collapsed rounded shadow" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne" id="fldrop">
        Список файлов
      </button>
    </h2>
    <div id="flush-collapseOne" class="accordion-collapse collapse" aria-labelledby="flush-headingOne" data-bs-parent="#accordionFlushExample">
      <div class="accordion-body table-responsive">
      <table class="table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Имя Файла</th>
              <th scope="col">Дейсвтие</th>
            </tr>
          </thead>
          <tbody id="filetable">
          </tbody>
          </table>
      </div>
    </div>
  </div>
  </div>
  <script>document.addEventListener("DOMContentLoaded",function(){get_file_list()})</script>
  </div>
</div>
<?php if (isset($settings["access"][$_SESSION["steamid"]])){ ?>
  <div class="accordion" id="accordionDada" data-aos="zoom-in" data-aos-delay="100">
  <?php if (isset($settings["access"][$_SESSION["steamid"]]["global"])){ ?>
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingOne">
        <button class="accordion-button fw-bold collapsed  shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false"  aria-controls="collapseOne"><i class="bi bi-globe-europe-africa"></i>&nbsp;Глобальные Настройки</button>
      </h2>
        <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#accordionDada">
          <div class="accordion-body shadow border-light d-flex flex-wrap justify-content-around column-gap-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="enable_news" onclick="toggleswitch(this)" <?php echo getSetting("enable_news",true)?"checked":"";?>>
              <label class="form-check-label" for="enable_news">Раздел новостей</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="enable_help" onclick="toggleswitch(this)" <?php echo getSetting("enable_help",true)?"checked":"";?>>
              <label class="form-check-label" for="enable_help">Раздел помощи</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="enable_banlist" onclick="toggleswitch(this)" <?php echo getSetting("enable_banlist",true)?"checked":"";?>>
              <label class="form-check-label" for="enable_banlist">Раздел банов</label>
            </div>
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" role="switch" id="enable_serverlist" onclick="toggleswitch(this)" <?php echo getSetting("enable_serverlist",true)?"checked":"";?>>
              <label class="form-check-label" for="enable_serverlist">Отображать сервера</label>
            </div>

            <div class="mt-3 input-group input-group-sm">
              <span class="input-group-text">Задний Фон</span>
              <input type="color" class="form-control form-control-color shadow-none" id="BGColorInput" value="#1e3a3d" title="Gradient #1">
              <input type="color" class="form-control form-control-color shadow-none" id="BGColorInput2" value="#752443" title="Gradient #2">
              <input type="color" class="form-control form-control-color shadow-none" id="BGColorInput3" value="#122d36" title="Gradient #3">
              <input type="color" class="form-control form-control-color shadow-none" id="BGColorInput4" value="#682727" title="Gradient #4">
              <button class="btn btn-outline-secondary" id="savebg" type="button"><i class="bi bi-palette"></i> Применить</button>
            </div>

            <div class="input-group mt-3">
              <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="optionDrop">Настройка</button>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" id="project_name" onclick="toggledrop(this)">Имя проекта</a></li>
                <li><a class="dropdown-item" id="favicon" onclick="toggledrop(this)">Путь к favicon</a></li>
                <li><a class="dropdown-item" id="donate_goal" onclick="toggledrop(this)">Месячная цель пожертвования</a></li>
                <li><a class="dropdown-item" id="goal_text" onclick="toggledrop(this)">Текст месячной цели</a></li>
                <li><a class="dropdown-item" id="donate_currency" onclick="toggledrop(this)">Валюта пожертвования</a></li>
                <li><a class="dropdown-item" id="tos" onclick="toggledrop(this)">Ссылка на TOS</a></li>
              </ul>
              <input type="text" class="form-control shadow-none outline-dark" id="dropInput" aria-label="text input">
              <button class="btn btn-outline-secondary" id="saveDrop" type="button"><i class="bi bi-database-fill-up"></i> Сохранить</button>
            </div>
          </div>
      </div>
    </div>
    <?php } ?>

    <?php if (isset($settings["access"][$_SESSION["steamid"]]["servers"])){ ?>
    <script>document.addEventListener("DOMContentLoaded",function(){get_servers()})</script>
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingTwo">
        <button class="accordion-button collapsed fw-bold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false"   aria-controls="collapseTwo"><i class="bi bi-hdd-rack-fill"></i>&nbsp;Управление Серверами</button>
      </h2>
      <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#accordionDada">
        <div class="accordion-body table-responsive shadow border-light">
          <table class="table table-sm">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Имя</th>
              <th scope="col">IP</th>
              <th scope="col">Port</th>
              <th scope="col">Действие</th>
            </tr>
          </thead>
          <tbody id="servertable">
          </tbody>
          </table>
          <form class="input-group input-group-sm mb-2" id="srv_form">
            <input type="text" class="form-control shadow-none" placeholder="Имя" name="name" required>
            <input type="text" class="form-control shadow-none" placeholder="IP" name="ip" required>
            <input type="number" class="form-control shadow-none" placeholder="Port" name="port" required>
            <button class="btn border-success fw-bold btn-success btn-sm col-2" type="submit" id="button-addon2"><i class="bi bi-database-fill-up"></i> Добавить</button>
          </frorm>
          </div> 
      </div>
    </div>
    <?php } ?>

    <?php if (isset($settings["access"][$_SESSION["steamid"]]["notes"])){ ?>
    <script>document.addEventListener("DOMContentLoaded",function(){get_notes()})</script>
    <div class="accordion-item">
      <h2 class="accordion-header" id="headingThree">
        <button class="accordion-button collapsed fw-bold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree"><i class="bi bi-newspaper"></i>&nbsp;Редактирование Записей</button>
      </h2>
        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#accordionDada">
          <div class="accordion-body table-responsive shadow border-light">
          <div class="mt-1 input-group" id="newsform">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="write_selector">Выберите тип</button>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" id="news" onclick="toggleWritedrop(this)">Новость</a></li>
              <li><a class="dropdown-item" id="help" onclick="toggleWritedrop(this)">Помощь</a></li>
            </ul>
            <input type="text" class="form-control shadow-none" id="newstitle" placeholder="title">
            <input type="text" class="form-control shadow-none" id="newsheadimg" placeholder="image url">
            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#write_modal"><i class="bi bi-database-fill-up" ></i> Написать</button>
          </div>
          <div id="writeralert"></div>
          <table class="table table-sm mt-4">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Тип</th>
              <th scope="col">Название</th>
              <th scope="col">Действие</th>
            </tr>
          </thead>
          <tbody id="notetable">
          </tbody>
          </table>
      </div>
      </div>
    </div>
    <?php } ?>

    <?php if (isset($settings["access"][$_SESSION["steamid"]]["rcon"])){ ?>
    <div class="accordion-item">
        <h2 class="accordion-header" id="headingFour">
          <button class="accordion-button collapsed fw-bold shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour"><i class="bi bi-terminal-fill"></i>&nbsp;RCON</button>
        </h2>
        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#accordionDada">
          <div class="accordion-body shadow border-light">
            <div class="mt-1 input-group " id="rconform" action="core/rcon.php" method="post">
              <span class="input-group-text" id="inpttl"><i class="bi bi-terminal"></i></span>
              <input type="text" class="form-control shadow-none" id="rcon_string" name="command" placeholder="command">
              <select name="server" class="form-select shadow-none" id="rcon_servs" style="width: 20px">
              </select>
              <button class="btn btn-outline-secondary" id="rcon_submit" type="button">Run</button>
            </div>
            <div class="rcon_r d-none mt-4" id="typer">
              <div class="c2"><div class="text-lowercase typed-out" id="rcon_response_place"></div>
            </div>
          </div>
          </div>
        </div>
        <?php } ?>
  </div>
  <?php } ?>
    <div class="card mt-4" data-aos-offset="0" data-aos="flip-left" data-aos-delay="100">
      <div class="card-header text-black fw-bold">
        Дополнительная Информация
      </div>
      <div class="card-body shadow border-light d-flex flex-wrap justify-content-around column-gap-3">
        <p class="fw-bold" id="filesinf"></p>
        <p class="fw-bold text-uppercase">АКТИВ НА САЙТЕ: <span id="sitetime"><?=elapsed($userdata["last_online"]);?></span></p>
        <p class="fw-bold text-uppercase">АКТИВ НА СЕРВЕРЕ: <span id="servertime"><?=$userdata["last_played"]?elapsed($userdata["last_played"]):"НИКОГДА";?></span></p>
      </div>
    </div>
  <script src="js/pip.min.js"></script>
<?php }else{ ?>
    <h1 class="text-danger" data-aos="zoom-in" data-aos-delay="100">ОШИБКА</h1>
    <p class="lead" data-aos="fade-down" data-aos-delay="400"><?=$_GET["error"];?></p>
    <p class="lead">
    <a class="btn mt-3 position-relative fixed-bottom btn-sm btn-secondary fw-bold border-white bg-white" data-aos="zoom-in-up" data-aos-delay="500" data-aos-offset="0" href="?login">Войдите через
      <i class="bi bi-steam"></i>
       </a>
<?php }?>
<?php include("components/footer.php") ?>