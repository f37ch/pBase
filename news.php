<?php 
  include("components/head.php");
  $_GET["page"]="news";
  include("components/header.php");
  $page=isset($_GET["pg"]) ? $_GET["pg"] : 1;;
  $limit=4;
  $start = ($page-1) * $limit;
  $result=$database->query("SELECT * FROM notes WHERE type='news' ORDER BY id DESC LIMIT $start, $limit")??NULL;
  $countres=$database->query("SELECT count(id) AS id FROM notes WHERE type='news'")??NULL;
  $fetchedcount = $countres->fetch_all(MYSQLI_ASSOC);
  $total = $fetchedcount[0]["id"];
  $pages = ceil($total/$limit);
  $prev = $page>1?$page-1:1;
	$nxt = $page!=$pages?$page+1:$pages;
  if(isset($_GET["id"])){
    $id=$_GET["id"];
    $data=$database->query("SELECT * FROM notes WHERE type='news' and id='$id'")->fetch_array();
    $userdata=getSteamData($data["steamid"]);
    ?>
      <h1 class="pn" data-aos="flip-right" data-aos-delay="200"><?=$data["title"]?></h1>
      <div data-aos="zoom-in" data-aos-delay="100">
      <hr>
        <img class="mb-4" src="<?=$data["headimg"]?>">
        <?=base64_decode($data["content"])?>
        <hr>
        <i class="fw-bold d-inline-block" style="float:right;padding-right: 5px;"><a style="text-decoration: none;" class="text-white" href="https://steamcommunity.com/profiles/<?=$userdata["steamid"]?>"><img src="<?php echo $userdata["avatarfull"]?>" style="width:27px;height:27px; border-radius:50%;"> <u><?=$userdata["name"]?></u></a>&nbsp[<?=date('Y-m-d H:i',$data["created"]);?>]</i>
      </div>
  <?php
  }elseif (mysqli_num_rows($result)) {
  while ($row=$result->fetch_assoc()):
?>
<div class="card text-bg-dark mb-3 hoverscale" style="overflow: hidden;" data-aos="zoom-out" data-aos-delay="100">
  <div class="image-box">
  <a href="?id=<?=$row['id'];?>"><img src="<?=$row["headimg"];?>" class="card-img" style="object-fit: cover; height: 17vh;">
  </div>
    <h1 class="text-white pn position-absolute top-50 start-50 translate-middle" style="width:100%;background-color:rgba(0,0,0,.5);cursor:pointer;"><?=$row["title"];?></h1>
    </a>
  </div>
<?php endwhile ?>
<?php if ($pages>1){ ?>
<ul class="pagination justify-content-right mt-4" data-aos-offset="0" data-aos="fade-up" data-aos-delay="200">
  <?php if($page>4) { ?>
    <li class="page-item">
      <a class="page-link text-black shadow-none" href="news.php?pg=1">
        <span aria-hidden="true">&laquo;</span>
      </a>
    </li>
  <?php } ?>
  <li class="page-item <?= $page==1?"disabled":""; ?>">
    <a class="page-link text-black shadow-none" href="news.php?pg=<?= $prev; ?>">
      <span aria-hidden="true">Prev</span>
    </a>
  </li>
  <?php for($i = max(1,$page-3); $i < min($pages+1,$page+3); $i++) { ?>
		<li class="page-item"><a class="page-link shadow-none text-black <?= $page==$i?"active":""; ?>" href="news.php?pg=<?= $i; ?>"><?= $i; ?></a></li>
	<?php }; ?>
  <li class="page-item <?= $page==$pages?"disabled":""; ?>">
    <a class="page-link text-black shadow-none" href="news.php?pg=<?= $nxt; ?>">
      <span aria-hidden="true">Next</span>
    </a>
  </li>
  <?php if($page<$pages-2) { ?>
    <li class="page-item">
      <a class="page-link text-black shadow-none" href="news.php?pg=<?=$pages;?>">
        <span aria-hidden="true">&raquo;</span>
      </a>
    </li>
  <?php } ?>
</ul>
<?php }}else{ ?>
  <h4 data-aos="zoom-out" data-aos-delay="200" class="ql-align-center text-danger">so far no news.</h4>
<?php } include("components/footer.php")?>