<?php include("components/head.php") ?>
<h3 class="mb-3 font_big" id="project_name"><?php echo getSetting("project_name",false)??"pBase"?></h3>
<div data-aos="zoom-in" data-aos-delay="100" class="card mt-auto mb-auto border-0 bggrad text-white" style="border-radius:20px; overflow: hidden; height: auto;">
  <div class="d-flex mt-1 p-2" style="overflow: hidden;height:15vw;">
    <img id="gm_img" class="rounded-4 mb-1" style="height:100%;aspect-ratio:1/1;" src="https://i.imgur.com/HKIws2U.png">
    <div class="d-flex fw-bold mt-1 flex-column" style="justify-content: center;width: 100%;">
      <h1 id="gameinf">err</h1>
      <h5 id="mapinf">err</h5>
      <h5 id="playercnt">err</h5>
    </div>
  </div> 
</div>
<h6 id="words" class="fw-bold mb-auto" style="opacity: 0;transition: opacity 1s ease;"></h6>
<?php $result=$database->query("SELECT * FROM notes WHERE type='news' ORDER BY id DESC LIMIT 3")??NULL;?>
<?php $counter = 0;?>
<div class="mt-2" data-aos="flip-right" data-aos-delay="100">
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
    <a href="/news.php?id=<?=$row['id'];?>"><img src="<?=$row["headimg"];?>" class="d-block w-100" alt="..." style="object-fit: cover; height: 22vh;">
      <h1 class="text-white font_smol position-absolute top-50 start-50 translate-middle" style="width:100%; background-color:rgba(0,0,0,.5);cursor:pointer;"><?=$row["title"];?></h1>
    </a>
  </div>
  </div>
  <?php endwhile; ?>
</div>
</div>
<p id="curm" class="fw-bold mt-3" style="text-align: right;">Играет: </p>
</div>
<script>
  var songs=<?php echo json_encode($settings["loading_music"]); ?>;
  var words=<?php echo json_encode($settings["loading_words"]); ?>;
  var imgs=<?php echo json_encode($settings["loading_imgs"]); ?>;
  
  var volume = <?php echo $settings["loading_volume"]; ?>;
  function playRandomSong() {
    var randomIndex = Math.floor(Math.random() * songs.length);
    var song = songs[randomIndex];
    document.getElementById("curm").innerHTML=document.getElementById("curm").innerHTML+song.title
    var audio = new Audio(song.file);
    audio.volume=volume/100
    audio.play();
    audio.addEventListener("ended",function(){
      playRandomSong();
    });
  }
  playRandomSong();
  document.getElementById("gm_img").src=imgs["unknwn"];
  function GameDetails(servername,serverurl,mapname,maxplayers,steamid,gamemode) {
	  document.getElementById("gameinf").innerHTML=gamemode
    document.getElementById("mapinf").innerHTML="Карта: "+mapname
    document.getElementById("playercnt").innerHTML="Игровых слотов: "+maxplayers
    var gmlw=gamemode.toLowerCase()
    if (imgs.hasOwnProperty(gmlw)) {
      var value=imgs[gmlw];
      document.getElementById("gm_img").src=value
    }
  }
  var textContainer=document.getElementById("words");
  var fadeIn=function() {textContainer.style.opacity="1";};
  var fadeOut=function() {textContainer.style.opacity="0";};
  function updateText() {
    fadeOut();
    setTimeout(function(){
      textContainer.textContent=words[Math.floor(Math.random() * words.length)];
      fadeIn();
    },1000);
  }
  setInterval(updateText,8000);
  updateText();
</script>
<script src="<?=asset_version("/js/aos.js")?>"></script>
<?php include("components/footer.php") ?>