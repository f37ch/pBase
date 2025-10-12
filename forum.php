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
}elseif(isset($_GET["search"])){
    $search=mb_substr(trim($_GET["search"]??""),0,255);
    $search_escaped=$database->real_escape_string($search);
    $countQ=$database->query("
        SELECT COUNT(*) AS cnt
        FROM forum_posts p
        JOIN users u ON u.steamid = p.sid
        WHERE p.content LIKE '%{$search_escaped}%'
           OR u.name LIKE '%{$search_escaped}%'
           OR p.sid LIKE '%{$search_escaped}%'
    ");
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

<link href="<?=asset_version("/css/quill.snow.css")?>" rel="stylesheet">
<link href="<?=asset_version("/css/atom-one-dark.min.css")?>" rel="stylesheet">
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
  text-shadow: none;
}
#smiles {
  display: flex;
  align-items: center;
}
.reaction {
  display: flex;
  align-items: center;
  cursor: pointer;
  transition: transform 0.15s ease, filter 0.15s ease;
  user-select: none;
}
.reaction:hover {
  transform: scale(1.3);
  filter: brightness(1.2);
}
.reaction .emoji {
  font-size: 20px;
}
.reaction .count {
  margin-left: 4px;
  font-size: 14px;
  color: #555;
}
</style>

<script src="<?=asset_version("/js/highlight.min.js")?>"></script>
<script src="<?=asset_version("/js/quill.js")?>"></script>
<?php if (!isset($_GET["thread"])||(isset($_GET["thread"])&&$thread)){?>
<nav class="card mb-4 text-black"  data-aos="flip-right" data-aos-delay="100">
    <div class="card-body d-flex justify-content-between" style="padding: .5rem;">
        <h6 class="text-start mb-0 flex-grow-1 text-truncate" style="line-height:unset; max-width:calc(100%-100px);">
          <?php if (isset($_GET["thread"])){
            echo $thread["cat_name"]." > ".$thread["subcat_name"]." > ".$thread["topic"];
          }elseif(isset($search)){
            echo "Поиск по запросу «".htmlspecialchars($search)."»";
          }else{
            echo "Главная";}?>
        </h6>
        <div class="d-flex justify-content-around column-gap-3">
          <?php if (!isset($_GET["thread"])&&!isset($search)&&hasAccess("forum_admin")){ ?>
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
            <input type="text" class="form-control shadow-none" id="searchInput" placeholder="sid64 / ключевое слово" value="<?=htmlspecialchars($_GET["search"]??"")?>">
            <button class="btn btn-outline-secondary" type="button" onclick="location.href='?search='+encodeURIComponent(document.getElementById('searchInput').value)">Поиск</button>
          </div>
        </div>
      </div>
    </div>
</nav>
<?php } if (isset($search)){
$limit=8;
$total=$countQ->fetch_assoc()["cnt"]??0;

$pages=max(1,ceil($total/$limit));
$page=isset($_GET["pg"])?max(1,intval($_GET["pg"])):1;
if ($page>$pages) {$page=$pages;}
$start=($page-1)*$limit;

$resultsQ=$database->query("
    SELECT 
        p.id AS post_id,
        p.content,
        p.timestamp,
        t.id AS thread_id,
        u.name AS author_name,
        u.avatarfull,
        u.ugroup,
        u.steamid
    FROM forum_posts p
    JOIN forum_threads t ON t.id = p.thread_id
    JOIN users u ON u.steamid = p.sid
    WHERE p.content LIKE '%{$search_escaped}%'
       OR u.name LIKE '%{$search_escaped}%'
       OR p.sid LIKE '%{$search_escaped}%'
    ORDER BY p.timestamp DESC
    LIMIT {$start}, {$limit}
");  
if ($total==0){?>
        <p class="lead" data-aos="fade-down" data-aos-delay="400">По вашему запросу ничего не найдено.</p>
    <?php }else{
    $counter=0;
    while ($post=$resultsQ->fetch_assoc()){
    $rankname=$post["ugroup"]?$GLOBALS["settings"]["ugroups"][$post["ugroup"]]["name"]:"User";
    $rankcol=$post["ugroup"]?$GLOBALS["settings"]["ugroups"][$post["ugroup"]]["color"]:"rgba(71,71,71,1)";
    $counter++;
    $posQ=$database->query("
        SELECT COUNT(*) AS pos
        FROM forum_posts
        WHERE thread_id = {$post["thread_id"]}
          AND timestamp <= {$post["timestamp"]}
    ");
    $postNumber=$posQ->fetch_assoc()["pos"]??1;
    $postPage=ceil($postNumber/$limit);
    
?>
  <div id="post-<?=$post["post_id"]?>" class="card mb-3">
        <div class="d-flex">
          <div class="d-flex flex-grow-1 flex-column" style="overflow: auto;">
        <div class="card-header" style="padding: .5rem;">
          <div class="text-start d-flex align-items-center">
            <a href="/profile.php?id=<?=$post["sid"]?>" class="text-decoration-none text-black">
              <img class="col-auto rounded-circle" style="border: 4px solid rgba(71,71,71,1); width:5rem; min-width:5rem;" src="<?=$post["avatarfull"]?>">
            </a>
            <div class="p-2 text-truncate">
              <h5 class="mb-0 text-truncate"><?=$post["author_name"]?></h5>
              <span class="title text-truncate" style="color: <?=$rankcol?>;"><?=$rankname?></span>
              <div class="text-start text-truncate" title="<?=date("Y-m-d H:i:s",$post["timestamp"])?>"><?=elapsed($post["timestamp"])?></div>
            </div>
            <a class="text-decoration-none ms-auto p-2 text-black" title="Ссылка на пост" href="?thread=<?=$post["thread_id"]?>&pg=<?=$postPage?>#post-<?=$post["post_id"]?>">
                <h4><?=$thread["pinned"]?"<i class='bi bi-pin-angle-fill'></i> ":""?>#<?=$counter+($page>1?$limit*($page-1):0)?></h4>
            </a>
          </div>    
        </div>
        <div class="card-body pt-2 p-0">
        
          <span class="post-content" style="border:unset;" data-delta="<?=htmlspecialchars($post["content"],ENT_QUOTES,"UTF-8")?>"></span>
        </div>
        <div class="card-footer post-footer">
          <div class="d-flex flex-wrap">
            <a class="btn btn-sm btn-success ms-auto" href="?thread=<?=$post["thread_id"]?>#post-<?=$post["post_id"]?>">
              Перейти к посту
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php }?>
      <script>
        document.querySelectorAll(".post-content").forEach(function(el){
            const delta=JSON.parse(el.dataset.delta);
            const quill=new Quill(el,{
                readOnly:true,
                theme:"snow",
                modules:{toolbar:false,syntax:true}
            });
            quill.setContents(delta);
        });
      </script>
      <?php if ($pages>1){
          $prev=max(1,$page-1);
          $next=min($pages,$page+1);
          ?>
          <ul class="pagination justify-content-right mt-3">
            <?php if($page>4) { ?>
              <li class="page-item">
                <a class="page-link text-black shadow-none" href="?search=<?=urlencode($search)?>&pg=1">
                  <span aria-hidden="true">&laquo;</span>
                </a>
              </li>
            <?php } ?>
            <li class="page-item <?=$page==1?"disabled":""?>">
              <a class="page-link text-black shadow-none" href="?search=<?=urlencode($search)?>&pg=<?=$prev?>">Prev</a>
            </li>
            <?php for ($i=max(1,$page-3); $i<=min($pages,$page+3); $i++) { ?>
              <li class="page-item">
                <a class="page-link text-black shadow-none <?=$page==$i?"active":""?>" href="?search=<?=urlencode($search)?>&pg=<?=$i?>"><?=$i?></a>
              </li>
            <?php } ?>
            <li class="page-item <?=$page==$pages?"disabled":""?>">
              <a class="page-link text-black shadow-none" href="?search=<?=urlencode($search)?>&pg=<?=$next?>">Next</a>
            </li>
            <?php if($page<$pages-2){ ?>
              <li class="page-item">
                <a class="page-link text-black shadow-none" href="?search=<?=urlencode($search)?>&pg=<?=$pages?>">
                  <span aria-hidden="true">&raquo;</span>
                </a>
              </li>
            <?php } ?>
          </ul>
          <?php
      }
  }
}elseif(!isset($_GET["thread"])){ ?>
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
<script src="<?=asset_version("/js/forum_main.min.js")?>"></script>
<?php }else{
    if (!$thread){ ?>
        <h1 class="text-danger" data-aos="zoom-in" data-aos-delay="100">ОШИБКА</h1>
        <p class="lead" data-aos="fade-down" data-aos-delay="400">Тред не найден!</p>
    <?php } else {//thread
        $limit=8;
        
        $countQ=$database->query("SELECT COUNT(*) as cnt FROM forum_posts WHERE thread_id = {$thread_id}");
        $total=$countQ->fetch_assoc()["cnt"]??0;
        $pages=ceil($total/$limit);

        $page=isset($_GET["pg"])?intval($_GET["pg"]):1;
        $page=max(1,min($page,$pages));
        $start=($page-1)*$limit;

        $prev=$page>1?$page-1:1;
        $next=$page<$pages?$page+1:$pages;

        $sid=$_SESSION["steamid"]??"";

        $postsQ=$database->query("
            SELECT 
                p.id, 
                p.sid,
                p.edited,
                p.content, 
                p.timestamp, 
                u.name, 
                u.avatarfull, 
                u.ugroup,
                (SELECT COUNT(*) FROM forum_posts WHERE sid = p.sid) AS user_posts,

                (SELECT COUNT(*) FROM forum_reactions r WHERE r.post_id = p.id AND r.reaction_type = 'like') AS `like`,
                (SELECT COUNT(*) FROM forum_reactions r WHERE r.post_id = p.id AND r.reaction_type = 'love') AS love,
                (SELECT COUNT(*) FROM forum_reactions r WHERE r.post_id = p.id AND r.reaction_type = 'funny') AS funny,
                (SELECT COUNT(*) FROM forum_reactions r WHERE r.post_id = p.id AND r.reaction_type = 'wow') AS wow,
                (SELECT COUNT(*) FROM forum_reactions r WHERE r.post_id = p.id AND r.reaction_type = 'sad') AS sad,

                (SELECT reaction_type FROM forum_reactions r WHERE r.post_id = p.id AND r.steamid = '$sid' LIMIT 1) AS my_reaction
            FROM forum_posts p
            JOIN users u ON u.steamid = p.sid
            WHERE p.thread_id = {$thread_id}
            ORDER BY p.timestamp ASC
            LIMIT {$start}, {$limit}
        ");

        ?>
        <?php
        $counter=0;
        while ($post=$postsQ->fetch_assoc()){
            $rankname=$post["ugroup"]?$GLOBALS["settings"]["ugroups"][$post["ugroup"]]["name"]:"User";
            $rankcol=$post["ugroup"]?$GLOBALS["settings"]["ugroups"][$post["ugroup"]]["color"]:"rgba(71, 71, 71, 1)";
            $counter++;
            $viscnt=$counter+($page>1?$limit*($page-1):0);
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
                          <div class="text-start text-truncate" title="<?=date("Y-m-d H:i:s",$post["timestamp"])?>"><?=elapsed($post["timestamp"]).($post["edited"]?"<span title='Изменено ".date("Y-m-d H:i:s",$post["edited"])."' style='color: #7c7c7cff;'> • <i class='bi bi-pencil'></i> ".elapsed($post["edited"])."</span>":"")?></div>
                        </div>
                      <a class="text-decoration-none ms-auto p-2 text-black" title="Ссылка на пост" href="#post-<?=$post["id"]?>">
                        <h4><?=$thread["pinned"]?"<i class='bi bi-pin-angle-fill'></i> ":""?>#<?=$viscnt?></h4>
                      </a>
                    </div>    
                  </div>
                  <div class="card-body pt-2 p-0">
                    <span class="post-content" style="border:unset;" data-delta="<?=htmlspecialchars($post["content"],ENT_QUOTES,"UTF-8")?>">
                      
                    </span>
                  </div>
                  <div class="card-footer post-footer">
                    <div class="d-flex flex-wrap">
                      <div class="d-flex gap-2 reactions">
                        <?php
                        $emojiList=[
                            "like"=>"👍",
                            "love"=>"❤️",
                            "funny"=>"😂",
                            "wow"=>"😮",
                            "sad"=>"😢"
                        ];
                        foreach ($emojiList as $type=>$emoji){
                            $count=$post[$type]??0;
                            echo "
                            <div class='reaction' data-pid='{$post["id"]}' data-type='{$type}'>
                                <span class='emoji'>{$emoji}</span>
                                ".($count>0?"<span class='count'>{$count}</span>":"<span class='count' style='display:none;'></span>")."
                            </div>";
                        }
                        ?>
                      </div>
                      <div class="d-flex gap-1" style="margin-left:auto;">
                      <?php if (hasAccess("forum_admin")||$post["sid"]==$sid){ ?>
                          <button class="btn btn-dark btn-sm thread-btn" data-action="edit_post" title="Изменить" data-id="<?=$post["id"]?>"><i class="bi bi-pencil"></i></button>
                      <?php } ?>
                      <?php if (hasAccess("forum_admin")){ if ($viscnt==1){ ?>
                          <button class="btn btn-dark btn-sm thread-btn" data-action="pin_thread" title="<?=$thread["pinned"]?"Открепить":"Закрепить"?>" data-id="<?=$thread_id?>">
                            <?=$thread["pinned"]?"<i class='bi bi-pin-angle'></i>":"<i class='bi bi-pin'></i>"?>
                          </button>
                          <button class="btn btn-dark btn-sm thread-btn" data-action="lock_thread" title="<?=$thread["locked"]?"Открыть":"Закрыть"?>" data-id="<?=$thread_id?>">
                            <?=$thread["locked"]?"<i class='bi bi-unlock'></i>":"<i class='bi bi-lock'></i>"?>
                          </button>
                      <?php } ?>
                          <button class="btn btn-danger btn-sm thread-btn" title="<?=$viscnt==1?"Удалить Тред":"Удалить Пост"?>"data-action="<?=$viscnt==1?"delete_thread":"delete_post"?>" data-id="<?=$viscnt==1?$thread_id:$post["id"]?>">
                            <?=$viscnt==1?"<i class='bi bi-x-square'></i>":"<i class='bi bi-trash3'></i>"?>
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
        <?php if ($pages>1) { ?>
          <ul class="pagination justify-content-right mt-3">
              <?php if($page>4) { ?>
                <li class="page-item">
                  <a class="page-link text-black shadow-none" href="?thread=<?=$thread_id?>&pg=1">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
              <?php } ?>
              <li class="page-item <?=$page==1?"disabled":""?>">
                <a class="page-link text-black shadow-none" href="?thread=<?=$thread_id?>&pg=<?=$prev?>">Prev</a>
              </li>
              <?php for ($i=max(1,$page-3); $i<=min($pages,$page+3); $i++) { ?>
                <li class="page-item">
                  <a class="page-link text-black shadow-none <?=$page==$i?"active":""?> <?=$page."---".$i?>" href="?thread=<?=$thread_id?>&pg=<?=$i?>"><?=$i?></a>
                </li>
              <?php } ?>
              <li class="page-item <?=$page==$pages?"disabled":""?>">
                <a class="page-link text-black shadow-none" href="?thread=<?=$thread_id?>&pg=<?=$next?>">Next</a>
              </li>
              <?php if($page<$pages-2){ ?>
                <li class="page-item">
                  <a class="page-link text-black shadow-none" href="?thread=<?=$thread_id?>&pg=<?=$pages;?>">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              <?php } ?>
          </ul>
        <?php } ?>
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
            <button type="button" id="clear" class="btn btn-danger btn-sm text-end">Очистить Поле</button>
            <button type="button" id="publish" class="btn btn-success btn-sm text-end" data-counter="<?=$counter?>">Ответить</button>
          </div>
          <?php } ?>
        </div>
        <?php }else{ ?>
          <a href="?login" class="btn btn-light fw-bold w-100 mb-3" type="button">Войдите Чтобы Написать Ответ</a>
        <?php } ?>
        <script src="<?=asset_version("/js/forum_thread.min.js")?>"></script>
    <?php } // if thread
}?>

<?php include("components/footer.php") ?>