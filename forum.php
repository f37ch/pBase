<?php
include("components/head.php");
$_GET["page"]="forum";
include("components/header.php");
if (!getSetting("enable_forum",true)){
  header("Location: /");
  exit;
}
if (isset($_GET["thread"])){//get thread
    $thread_id=intval($_GET["thread"]);
    $threadQ=$database->query("
        SELECT 
            t.id, 
            t.topic, 
            t.timestamp,
            t.pinned,
            t.locked,
            u.name AS author_name, 
            u.avatarfull, 
            u.ugroup,
            sc.name AS subcat_name,
            c.name AS cat_name
        FROM forum_threads t
        JOIN users u ON u.steamid = t.sid
        JOIN forum_subcats sc ON sc.id = t.subcat_id
        JOIN forum_cats c ON c.id = sc.cat_id
        WHERE t.id = {$thread_id}
        LIMIT 1
    ");
    $thread=$threadQ->fetch_assoc();
}else{//get cats
    $catsQ=$database->query("
        SELECT id, name, prior
        FROM forum_cats
        ORDER BY COALESCE(prior, 999) ASC, id ASC
    ");
    $cats=[];
    while ($cat=$catsQ->fetch_assoc()){
        $cats[]=$cat;
    }
}
?>

<link href="<?=asset_version("/css/quill.snow.css")?>" rel="stylesheet" >
<link href="<?=asset_version("/css/atom-one-dark.min.css")?>" rel="stylesheet" >
<style>
.subcat-container {
    padding-bottom:.5rem;
    border-bottom:1px solid #ccc;
}
.active>.page-link {
  z-index:3;
  color:var(--bs-pagination-active-color);
  border-color: #2b2b2b;
  background-color: #d4d4d4;

}
.post-content img {
  max-width:100%
}
.ql-editor {
  padding:.5rem;
  min-height:6rem;
}
</style>

<script src="<?=asset_version("/js/highlight.min.js")?>"></script>
<script src="<?=asset_version("/js/quill.js")?>"></script>
<?php if (!isset($_GET["thread"])||(isset($_GET["thread"])&&$thread)){?>
<nav class="card mb-4 text-black"  data-aos="flip-right" data-aos-delay="100">
    <div class="card-body d-flex justify-content-between" style="padding: .5rem;">
        <h6 class="text-start mb-0 flex-grow-1 text-truncate" style="line-height:unset; max-width:calc(100%-100px);"><?=isset($_GET["thread"])?($thread["cat_name"]." > ".$thread["subcat_name"]." > ".$thread["topic"]):"Главная";?></h6>
       
        <div class="d-flex justify-content-around column-gap-3">
          <?php if (!isset($_GET["thread"])&&hasAccess("forum_admin")){ ?>
            <i class="bi bi-plus-square text-end" data-bs-toggle="collapse" href="#admCollapse" role="button" aria-expanded="false" aria-controls="admCollapse" title="Добавить"></i>
          <?php }; ?>
          <i class="bi bi-search text-end" data-bs-toggle="collapse" href="#searchCollapse" role="button" aria-expanded="false" aria-controls="searchCollapse" title="Поиск"></i>
        </div>
    </div>
    
    <?php if (!isset($_GET["thread"])&&hasAccess("forum_admin")){ ?>
        <div class="collapse" id="admCollapse">
          <div class="card text-black" style="padding: .5rem;">
            <div class="input-group input-group-sm" >
              <select class="form-select w-100 shadow-none" id="action">
                <option value="new_cat" selected>Категория</option>
                <option value="new_subcat">Подкатегория</option>
              </select>

              <select class="form-select shadow-none" id="cat_fsub" style="display:none;">
                  <?php if (!empty($cats)): ?>
                      <?php foreach ($cats as $row): ?>
                          <option value="<?=$row["id"]?>">
                              <?=$row["name"]?>
                          </option>
                      <?php endforeach; ?>
                  <?php else: ?>
                      <option value="">Нет категорий</option>
                  <?php endif; ?>
              </select>

              <input type="text" id="name" class="form-control shadow-none" placeholder="Наименование...">
              <input type="number" id="prior" class="form-control shadow-none" placeholder="Приоритет..." style="max-width:130px;">
              <input type="text" id="icon" class="form-control shadow-none" placeholder="Иконка..." style="display:none;">
            </div>
            <button class="btn btn-outline-secondary w-100 btn-sm" id="createBtn" type="button">Создать</button>
          </div>
        </div>
    <?php }; ?>

    <div class="collapse" id="searchCollapse">
      <div class="card text-black">
        <div class="card-body justify-content-between" style="padding: .5rem;">
          <div class="input-group input-group-sm">
            <input type="text" class="form-control shadow-none" placeholder="sid64/ключевое слово">
            <button class="btn btn-outline-secondary" type="button">Поиск</button>
          </div>
        </div>
      </div>
    </div>
</nav>
<?php }; ?>




<?php if (!isset($_GET["thread"])){ ?>
<div class="modal fade text-black" id="write_modal" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="staticBackdropLabel"  aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title w-100 fs-5" id="modallbl">Новый тред в *сабкатегория*</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" >
        <div class="input-group mb-3" id="iittl">
          <span class="input-group-text"><i class="bi bi-fonts"></i></span>
          <input type="text" id="iinpttl" class="shadow-none form-control" placeholder="Тема поста..." aria-describedby="inpttl">
        </div>
        <div id="editor" style="min-height:20rem;">
          Введите текст...
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="cancel" class="btn btn-dark" data-bs-dismiss="modal">Отмена</button>
        <button type="button" id="publish" class="btn btn-success">Опубликовать</button>
      </div>
    </div>
  </div>
</div>
<?php
foreach ($cats as $cat):
  $subcats=[];
  $subQ=$database->query("
      SELECT s.id, s.name, s.icon, s.prior
      FROM forum_subcats s
      WHERE s.cat_id = {$cat["id"]}
      ORDER BY COALESCE(s.prior, 999) ASC, s.id ASC
  ");
  while($row=$subQ->fetch_assoc()){
    $subcats[]=$row;
  }
  $total=count($subcats);
?>
<div class="card mt-4" id="category-<?=$cat["id"]?>">
  <div class="card-header fw-bold d-flex justify-content-between">
      <span><?=$cat["name"]?></span>
      <?php if (hasAccess("forum_admin")){ ?>
      <i role="button" data-bs-toggle="collapse" href="#edit_collapse-<?=$cat["id"]?>" title="Редактировать" class="bi bi-pencil"></i>
      <?php } ?>
  </div>
  
  <?php if (hasAccess("forum_admin")){ ?>
  <div class="collapse" id="edit_collapse-<?=$cat["id"]?>">
      <div class="card text-black" style="padding: .5rem;">
        <div class="input-group input-group-sm">
          <?php  if ($total>0) { ?>
          <select class="form-select w-100 shadow-none edit-action" id="action_edit-<?=$cat["id"]?>">
            <option value="edit_cat" selected>Категория</option>
            <option value="edit_subcat">Подкатегории</option>
          </select>
          <select class="form-select w-100 shadow-none edit-catlist" id="cat_id-<?=$cat["id"]?>" style="display:none;">
            <?php  foreach ($subcats as $k=>$subcat){ ?>
              <option value="<?=$subcat["id"]?>" data-prior="<?=$subcat["prior"]?>" data-icon="<?=$subcat["icon"]?>" <?=$k===0?"selected":""?>><?=$subcat["name"]?></option>
            <?php  } ?>
          </select>
          <?php } ?>
          
          <input type="text" id="edit_name-<?=$cat["id"]?>" class="form-control shadow-none edit-name" placeholder="Наименование..." value="<?=$cat["name"]?>">
          <input type="number" id="edit_prior-<?=$cat["id"]?>" class="form-control shadow-none edit-prior" placeholder="Приоритет..." style="max-width:130px;" value="<?=$cat["prior"]?>">
          <input type="text" id="edit_icon-<?=$cat["id"]?>" class="form-control shadow-none edit-icon" placeholder="Иконка..." style="display:none;">
        </div>
        <div class="btn-group btn-group-sm" role="group">
          <button class="btn btn-outline-secondary w-100 edit-btn" id="edit_btn-<?=$cat["id"]?>" type="button">Редактировать</button>
          <button class="btn btn-outline-danger w-100 rm-btn" id="rm_btn-<?=$cat["id"]?>" type="button">Удалить</button>
        </div>
      </div>
    </div>
  <?php } ?>

  <div class="card-body">
<?php
//subcats
$index=0;
foreach ($subcats as $subcat):
    $index++;
    $collapseId="threadCollapse-".$subcat["id"];

    $lastThreadQ=$database->query("
        SELECT t.id AS thread_id,
               t.topic AS last_post_topic,
               u.name AS author_name,
               u.avatarfull AS author_avatar,
               p.timestamp AS last_time
        FROM forum_threads t
        JOIN forum_posts p ON p.id = (
            SELECT p2.id
            FROM forum_posts p2
            WHERE p2.thread_id = t.id
            ORDER BY p2.timestamp DESC
            LIMIT 1
        )
        JOIN users u ON u.steamid = p.sid
        WHERE t.subcat_id = {$subcat["id"]}
        ORDER BY p.timestamp DESC
        LIMIT 1
    ");
    $last=$lastThreadQ->fetch_assoc();

    $countQ=$database->query("
        SELECT COUNT(*) AS cnt
        FROM forum_posts p
        JOIN forum_threads t ON t.id = p.thread_id
        WHERE t.subcat_id = {$subcat["id"]}
    ");
    $count=$countQ->fetch_assoc()["cnt"]??0;
?>
    <div class="d-flex align-items-center mb-2 <?php if ($index<$total){echo "subcat-container";}; ?>" id="subcat-<?=$subcat["id"]?>">
        <?php if (!empty($subcat["icon"])): ?>
            <?php if (filter_var($subcat["icon"],FILTER_VALIDATE_URL)): ?>
                <span class="fs-1 d-inline-flex align-items-center justify-content-center" style="line-height:1;">
                    <img src="<?=$subcat["icon"]?>"
                    alt="icon"
                    style="width:1em; height:1em; display:inline-block; vertical-align:middle; object-fit:contain;">
                </span>
            <?php else: ?>
                <i class="fs-1 bi <?=$subcat["icon"]?> text-end"></i>
            <?php endif; ?>
        <?php else: ?>
            <i class="fs-1 bi bi-textarea-t text-end"></i>
        <?php endif; ?>
        <div class="p-2" data-bs-toggle="collapse"
             href="#<?= $collapseId ?>" role="button"
             aria-expanded="false" aria-controls="<?= $collapseId ?>">

            <h5 class="mb-0 text-start text-decoration-none text-body-secondary subcat-name"><?=$subcat["name"]?></h5>

            <?php if($last): ?>
            <div class="text-start" title="<?=$last["author_name"]?>" class="text-decoration-none text-body-secondary">
                <span class="fw-light">
                    <img class="col-auto rounded-circle"
                         style="border:1px solid rgba(71,71,71,1);width:1.5rem;"
                         src="<?=$last["author_avatar"]?>">
                    <?=mb_strimwidth($last["last_post_topic"],0,28,"…")?>
                </span>
                <span style="color:#178649ff;">• <?=elapsed($last["last_time"])?></span>
            </div>
            <?php else: ?>
            <div class="text-muted small text-start">Нет постов</div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="collapse" id="<?=$collapseId?>">
        <div id="threadList-<?=$subcat["id"]?>"></div>
            <?php if (isset($_SESSION["steamid"])){ ?>
              <button data-bs-toggle="modal" href="#write_modal" data-name="<?=$subcat["name"]?>" class="btn btn-outline-secondary w-100 btn-sm newpost-btn" id="newpost_btn-<?=$subcat["id"]?>" type="button">Создать Новый Тред</button>
            <?php }else{ ?>
              <a href="?login" class="btn btn-outline-secondary w-100 btn-sm" type="button">Войдите Чтобы Создать Тред</a>
            <?php } ?>
        <ul class="pagination pagination-sm mt-2 mb-0 d-none" id="threadPag-<?=$subcat["id"]?>"></ul>
        </div>
<?php endforeach; // subcats ?>
    </div>
</div>
<?php
endforeach; // cats?>
<script src="<?=asset_version("/js/forum_main.js")?>"></script>
<?php }else{
    if (!$thread){ ?>
        <h1 class="text-danger" data-aos="zoom-in" data-aos-delay="100">ОШИБКА</h1>
        <p class="lead" data-aos="fade-down" data-aos-delay="400">Тред не найден!</p>
    <?php } else {//thread

        $postsQ = $database->query("
            SELECT 
                p.id, 
                p.sid, 
                p.content, 
                p.timestamp, 
                u.name, 
                u.avatarfull, 
                u.ugroup,
                (SELECT COUNT(*) FROM forum_posts WHERE sid = p.sid) AS user_posts
            FROM forum_posts p
            JOIN users u ON u.steamid = p.sid
            WHERE p.thread_id = {$thread_id}
            ORDER BY p.timestamp ASC
        ");
        ?>
        <?php
        $counter=0;
        while ($post=$postsQ->fetch_assoc()){
            $rankname=$post["ugroup"]?$GLOBALS["settings"]["ugroups"][$post["ugroup"]]["name"]:"User";
            $rankcol=$post["ugroup"]?$GLOBALS["settings"]["ugroups"][$post["ugroup"]]["color"]:"rgba(71, 71, 71, 1)";
            $counter++;
            ?>
            <div id="post-<?=$post["id"]?>" class="card mb-3">
              <div class="d-flex">
                <div class="d-flex flex-grow-1 flex-column" style="overflow: auto;">
                  <div class="card-header" style="padding: .5rem;">
                    <div class="text-start d-flex align-items-center">
                      <a href="/profile.php?id=<?=$post["sid"]?>" class="text-decoration-none text-black">
                        <img class="col-auto rounded-circle" style="border: 4px solid rgba(71,71,71,1); width:5rem; min-width:5rem;" src="<?=$post["avatarfull"]?>">
                      </a>
                        <div class="p-2 text-truncate">
                          <h5 class="mb-0 text-truncate"><?=$post["name"]?></h5>
                          <span class="title text-truncate" style="color: <?=$rankcol?>;"><?=$rankname?></span>
                          <div class="text-start text-truncate" title="<?=date("Y-m-d H:i:s",$post["timestamp"])?>"><?=elapsed($post["timestamp"])?></div>
                        </div>
                      <h4 class="ms-auto p-2"><?=$thread["pinned"]?"<i class='bi bi-pin-angle-fill'></i> ":""?>#<?=$counter?></h4>
                    </div>    
                  </div>
                  <div class="card-body pt-2 p-0">
                    <span class="post-content" style="border:unset;" data-delta="<?=htmlspecialchars($post["content"],ENT_QUOTES,"UTF-8")?>">
                      
                    </span>
                  </div>
                  <div class="card-footer post-footer">
                    <div class="d-flex flex-wrap">
                      <div class="d-flex gap-1" style="margin-left:auto;">
                      <?php if (hasAccess("forum_admin")){ if ($counter==1){ ?>
                          <button class="btn btn-dark btn-sm thread-btn" data-action="pin_thread" data-id="<?=$thread_id?>">
                            <?=$thread["pinned"]?"Открепить":"Закрепить"?>
                          </button>
                          <button class="btn btn-dark btn-sm thread-btn" data-action="lock_thread" data-id="<?=$thread_id?>">
                            <?=$thread["locked"]?"Открыть":"Закрыть"?>
                          </button>
                      <?php } ?>
                          <button class="btn btn-danger btn-sm thread-btn" data-action="<?=$counter==1?"delete_thread":"delete_post"?>" data-id="<?=$counter==1?$thread_id:$post["id"]?>">Удалить<?=$counter==1?" Тред":""?>
                          </button>
                      <?php } ?>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <?php
        } // while?>
        <?php if (isset($_SESSION["steamid"])){ ?>
        <div class="card mb-3">
          <?php if ($thread["locked"]){ ?>
          <div class="card-body col-red p-1">
            <h3>Тред Был Закрыт</h3>
            <h2><i class="bi bi-lock"></i></h2>
          </div>
          <?php }else{ ?>
            <div class="card-body p-0">
            <span id="editor" style="border:unset;"></span>
          </div>
          <div class="card-footer d-flex justify-content-end gap-2">
            <button type="button" id="clear" class="btn btn-danger btn-sm text-end" onclick="window.quill.setContents()">Очистить Поле</button>
            <button type="button" id="publish" class="btn btn-success btn-sm text-end">Ответить</button>
          </div>
          <?php } ?>
        </div>
        <?php }else{ ?>
          <a href="?login" class="btn btn-light fw-bold w-100 mb-3" type="button">Войдите Чтобы Написать Ответ</a>
        <?php } ?>
        <script src="<?=asset_version("/js/forum_thread.js")?>"></script>
    <?php } // if thread
}?>


<?php include("components/footer.php") ?>