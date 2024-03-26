<?php include("components/head.php");
$_GET["page"]="bans";
include("components/header.php");
$page=isset($_GET['pg'])?intval($_GET['pg']):1;;
$limit=13;
$start=($page-1)*$limit;
$type=isset($_GET["type"])?"WHERE type='".$database->real_escape_string($_GET["type"])."'":"";
$wherend=isset($_GET["type"])?"AND":"WHERE";
$sid=isset($_GET["sid"])&&$_GET["sid"]!=""?$wherend." (offender_steamid='".$database->real_escape_string($_GET["sid"])."' or admin_steamid='".$database->real_escape_string($_GET["sid"])."')":"";
$result=$database->query("SELECT * FROM bans $type $sid ORDER BY id DESC LIMIT $start, $limit")??NULL;
$countres=$database->query("SELECT count(id) AS id FROM bans $type $sid")??NULL;
$fetchedcount=$countres->fetch_all(MYSQLI_ASSOC);
$total=$fetchedcount[0]['id'];
$pages=ceil($total/$limit);
$prev=$page>1?$page-1:1;
$nxt=$page!=$pages?$page+1:$pages;
function plural($n,$a,$b,$c) {
  switch($n%10==1&&$n%100!=11?0:($n%10>=2&&$n%10<=4&&($n%100<10or$n%100>=20)?1:2)){case 0:default:return $a;case 1:return $b;case 2:return $c;}
}
function elapsed($created,$expire)
{
    $rtime=$expire-$created;
    if ($rtime<1){return "навсегда";}
    $a=array(365*24*60*60=>"год",30*24*60*60=>"месяц",24*60*60=>"день",60*60=>"час",60 =>"минута",1=>"секунда");
    $a_da=array("год"=>["год","года","лет"],"месяц"=>["месяц","месяца","месяцев"],"день"=>["день","дня","дней"],"час"=>["час","часа","часов"],"минута"=>["минута","минуты","минут"],"секунда"=>["секунда","секунды","секунд"]);
    foreach ($a as $si=>$str)
    {
      $d=$rtime/$si;
      if ($d>=1)
      {
        $r=round($d);
        return $r." ".plural($r,$a_da[$str][0],$a_da[$str][1],$a_da[$str][2]);
      }
    }
}
$types=$database->query("SELECT DISTINCT(type) FROM bans")??NULL;
?>
<script>
 href = new URL(location);
</script>
<div class="input-group mb-3" data-aos="flip-right" data-aos-delay="100">
  <span class="input-group-text" id="da"><i class="bi bi-steam"></i></span>
  <input type="number" onchange="href.searchParams.delete('pg');href.searchParams.set('sid',this.value); location = href.toString()" class="form-control shadow-none" placeholder="steamid64" value="<?php echo $_GET["sid"]??""?>" aria-describedby="da" >
  <select class="form-select shadow-none" name="svid" title="select type" style="width: 20px" onchange="href.searchParams.delete('pg');if (this.value!='both'){href.searchParams.set('type',this.value); location = href.toString() }else{href.searchParams.delete('type'); location = href.toString()}">
  <option value="both" <?php echo !isset($_GET["type"])?"selected":""?>>Все типы</option>
    <?php while ($row=$types->fetch_assoc()):?>
      <option href="?<?php echo $row["type"]; ?>" value="<?php echo $row["type"]; ?>" <?php echo isset($_GET["type"])&&$_GET["type"]==$row["type"]?"selected":""; ?>><?php echo $row["type"]; ?></option>
    <?php endwhile;?>
  </select>
</div>


<div class="table-responsive" data-aos="zoom-in" data-aos-delay="150">
    <table class="table table-bordered table-light table-striped btable">
    	<thead>
    		<tr>
    		    <th scope="col" style="width:18%">Дата</th>
			      <th scope="col" style="width:10%">Сервер</th>
            <th scope="col" style="width:6%">Тип</th>
				    <th scope="col">Нарушитель</th>
			      <th scope="col">Админ</th>
            <th scope="col"style="width:13%">Длина</th>
            <th scope="col">Причина</th>
    		</tr>
    	</thead>
    	<tbody>
            <?php while ($row=$result->fetch_assoc()):?>
                <?php $offendersteam=getSteamData($row["offender_steamid"]);?>
                <?php $adminsteam=getSteamData($row["admin_steamid"]);?>
                <tr>
                    <td class="align-middle"><?php echo date('Y-m-d H:i',$row["created"]);?></td>
                    <td class="align-middle"><?php echo $row["server"];?></td>
                    <td class="align-middle" title="<?php echo $row["type"];?>"><?php echo $settings["bans_typeicons"][$row["type"]];?></td>
                    <td class="align-middle text-start" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                      <a title="<?php echo $offendersteam["name"];?>" href="https://steamcommunity.com/profiles/<?=$row["offender_steamid"]?>" class="text-black" style="text-decoration: none;"><?php echo $offendersteam["name"];?></a>
                    </td>
                    <td class="align-middle text-start" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                        <?php if($adminsteam){?>
                            <a title="<?php echo $adminsteam["name"];?>" href="https://steamcommunity.com/profiles/<?=$row["admin_steamid"]?>" class="text-black" style="text-decoration: none"><?php echo $adminsteam["name"];?></a>
                        <?php }else{ ?>
                            <i class='bi bi-terminal'></i> Console
                        <?php } ?>
                    </td>
                    <td class="align-middle"><?php echo elapsed($row["created"],$row["expires"]);?></td>
                    <td class="align-middle text-start" title="<?php echo $row["reason"]??"no reason given";?>" style="white-space: nowrap;overflow: hidden;text-overflow: ellipsis;"><?php echo $row["reason"]??"no reason given";?></td>
                </tr>
            <?php endwhile;?>
    	</tbody>
    </table>
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