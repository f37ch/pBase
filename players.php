<?php include("components/head.php");
$_GET["page"]="players";
include("components/header.php");
if (!getSetting("enable_players",true)) {
    header("Location: /");
    exit;
}
$page=isset($_GET["pg"])?intval($_GET["pg"]):1;
$limit=12;
$start=($page-1)*$limit;
$stypes=["Имя"=>"name","Последняя игра"=>"last_played","Регистрация"=>"registered","Последний онлайн"=>"last_online"];
$allowed_columns=array_values($stypes);
$sort_column="last_played";
if (isset($_GET["type"]) && in_array($_GET["type"],$allowed_columns,true)){
    $sort_column=$_GET["type"];
}
$where="";
if (isset($_GET["search"])&&$_GET["search"]!==""){
  $search=$database->real_escape_string($_GET["search"]);
  if (preg_match('/^7656\d{13}$/',$search)){
      $where="WHERE steamid = '$search'";
  } else {
      $where="WHERE name LIKE '%$search%'";
  }
}
$result=$database->query("SELECT * FROM users $where ORDER BY $sort_column DESC LIMIT $start, $limit")??NULL;
$countres=$database->query("SELECT count(steamid) AS cnt FROM users $where") ?? NULL;
$fetchedcount=$countres->fetch_all(MYSQLI_ASSOC);
$total=$fetchedcount[0]["cnt"];
$pages=ceil($total/$limit);
$prev=$page>1?$page-1:1;
$nxt=$page!=$pages?$page+1:$pages;
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
<script>
href=new URL(location);
</script>
<div class="d-flex justify-content-between align-items-center mb-4">
  <div class="input-group" data-aos="flip-right" data-aos-delay="100" style="width: 100%;">
    <span class="input-group-text" id="da"><i class="bi bi-steam"></i></span>
    <input type="text" onchange="href.searchParams.delete('pg');href.searchParams.set('search',this.value); location = href.toString()" class="form-control shadow-none" placeholder="steamid64/ник" value="<?php echo $_GET["search"]??""?>" aria-describedby="da" >
    <select class="form-select shadow-none" name="svid" title="select type" style="width: 20px" onchange="href.searchParams.delete('pg');href.searchParams.set('type',this.value); location = href.toString()">
      <?php foreach ($stypes as $label => $type):?>
        <option value="<?php echo $type; ?>" <?php
            if ((isset($_GET["type"])&&$_GET["type"]==$type)||(!isset($_GET["type"])&&$type=="last_played")) echo "selected";
        ?>><?php echo $label; ?></option>
      <?php endforeach;?>
    </select>
    <span class="btn btn-secondary fw-bold bg-white pulse-red"><i class="bi bi-person-circle"></i> Всего игроков: <?=$total?></span>
  </div>
</div>
<div class="row d-flex flex-wrap justify-content-around column-gap-1 d-flex">
<?php
$result->data_seek(0);
$stype=$_GET["type"]??null;
while ($row=$result->fetch_assoc()):?>
    <div class="card mb-4 text-black hoverscale stuser" style="border-radius:25px; width:250px; cursor: pointer; display:inline-block;">
        <div class="card-body">
            <div class="row p-1 mb-1">
                <div class="col text-center">
                    <img class="col-auto rounded-circle mb-3" style="border: 4px solid #000;" src="<?=htmlspecialchars($row["avatarfull"]??"",ENT_QUOTES,"UTF-8")?>" onclick="window.open('https://steamcommunity.com/profiles/<?=$row["steamid"]?>','_blank')"> <!-- width: 80px; adjust in style?-->
                    <h4 class='title my-0'><?=htmlspecialchars($row["name"]??"Unknown",ENT_QUOTES,"UTF-8")?></h4>

                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" style="font-weight: bold; width:100%" onclick="navigator.clipboard.writeText('<?=$row["steamid"]?>'); this.innerHTML='<i class=&quot;bi bi-steam&quot;></i> Скопировано!'; setTimeout(()=>{this.innerHTML='<i class=&quot;bi bi-steam&quot;></i> Копировать sid64';},1200);"><i class="bi bi-steam"></i> Копировать sid64</button>
                    
                    <?php if ($stype==="registered"):?>
                        <small class="title" style="color:#46B7AA; font-weight: bold;">
                            Зарегистрирован: <?=$row["registered"]?date("Y-m-d",$row["registered"]):"Никогда"?>
                        </small>
                    <?php elseif ($stype==="last_online"):?>
                        <small class="title" style="color:#46B7AA; font-weight: bold;">
                            Последний онлайн: <?=$row["last_online"]?elapsed($row["last_online"]):"Никогда"?>
                        </small>
                    <?php else: ?>
                        <small class="title" style="color:#46B7AA; font-weight: bold;">
                            Последняя игра: <?=$row["last_played"]?elapsed($row["last_played"]):"Никогда"?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>

<?php if ($pages>1){ ?>
<ul class="pagination justify-content-left" data-aos-offset="0" data-aos="fade-up" data-aos-delay="200">
  <?php if($page>4) { ?>
    <li class="page-item" onclick="href.searchParams.set('pg',1); location = href.toString()">
      <a class="page-link text-black shadow-none">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>
  <?php } ?>
  <li class="page-item <?= $page==1?"disabled":""; ?>" onclick="if (!this.classList.contains('disabled')){href.searchParams.set('pg','<?= $prev; ?>'); location = href.toString()}">
    <a class="page-link text-black shadow-none">
      <span aria-hidden="true">Prev</span>
    </a>
  </li>
  <?php for($i = max(1,$page-3); $i < min($pages+1,$page+3); $i++) { ?>
		<li class="page-item" onclick="href.searchParams.set('pg','<?= $i; ?>'); location = href.toString()"><a class="page-link shadow-none text-black <?= $page==$i?"active":""; ?>" ><?= $i; ?></a></li>
	<?php }; ?>
  <li class="page-item <?= $page==$pages?"disabled":""; ?>"onclick="if (!this.classList.contains('disabled')){href.searchParams.set('pg','<?= $nxt; ?>'); location = href.toString()}">
    <a class="page-link text-black shadow-none">
      <span aria-hidden="true">Next</span>
    </a>
  </li>
  <?php if($page<$pages-2) { ?>
    <li class="page-item" onclick="href.searchParams.set('pg','<?=$pages;?>'); location = href.toString()">
      <a class="page-link text-black shadow-none">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>
  <?php } ?>
</ul>
<?php } ?>
<?php include("components/footer.php") ?>