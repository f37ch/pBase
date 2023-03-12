<?php include("components/head.php") ?>
<?php include("components/header.php") ?>
  <?php $result=$database->query("SELECT * FROM notes WHERE type='news' ORDER BY id DESC LIMIT 3")??NULL;?>
  <?php $counter = 0;?>
  <div class="modal fade text-black" id="atakda" aria-hidden="true" id="staticBackdrop" data-bs-keyboard="false" aria-labelledby="staticBackdropLabel">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content">
        <div class="modal-header text-center">
          <h1 class="modal-title w-100 fs-5" id="staticBackdropLabel">List</h1>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="edamodal">
        </div>
      </div>
    </div>
  </div>
  <div data-aos="zoom-out" data-aos-delay="300">
  <div id="carousel" class="carousel slide hoverscale" data-bs-ride="carousel" style="border-radius:25px; overflow: hidden;" >
    <div class="carousel-indicators">
      <button type="button" data-bs-target="#carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
      <button type="button" data-bs-target="#carousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
      <button type="button" data-bs-target="#carousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>
    <div class="carousel-inner">
      <?php while ($row=$result->fetch_assoc()):?>
      <?php $counter++; ?>
      <div class="carousel-item <?php echo $counter==1?"active":"eba";?>">
      <div class="image-box">
        <a href="/news.php?id=<?=$row['id'];?>"><img src="<?=$row["headimg"];?>" class="d-block w-100" alt="..." style="object-fit: cover; height: 42vh;">
          <h1 class="text-white pn position-absolute top-50 start-50 translate-middle" style="width:100%; background-color:rgba(0,0,0,.5);cursor:pointer;"><?=$row["title"];?></h1>
        </a>
      </div>
      </div>
      <?php endwhile; ?>
    </div>
    </div>
    </div>
  <?php if (getSetting("enable_serverlist",true)){?>
    <div id="serverList" data-aos="fade-up" data-aos-offset="0" data-aos-delay="300">
      <h2 class="pn mt-3 mb-3" data-aos="zoom-in" data-aos-delay="100">НАШИ СЕРВЕРЫ</h2>
    </div>
  <?php }?>
  <script src="js/index.min.js"></script>
<?php include("components/footer.php") ?>