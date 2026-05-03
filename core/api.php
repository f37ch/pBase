<?php
require_once("db.php");
require_once("steamauth/steamauth.php");

//Synch bans and activity
if (isset($_GET["synch_user"]))
{
    if ($settings["api_key"]!=$_GET["api_key"]){
        http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
    }
    $sid=$_GET["synch_user"];
    getSteamData($sid);
    $database->query("UPDATE users SET last_played=UNIX_TIMESTAMP(NOW()) where steamid='$sid'");
    echo json_encode(array("success"=>"User synched successfully."));
}

//Get donations data
if (isset($_GET["donations"]))
{
    if ($settings["api_key"]!=$_GET["api_key"]){
        http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
    }
    $topsupp=$database->query("SELECT steamid,SUM(credits) FROM transactions GROUP BY steamid ORDER BY SUM(credits) DESC LIMIT 10");
    $recentdon=$database->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 10");
    $mgoal=$database->query("SELECT value FROM global_settings WHERE name = 'donate_goal'")->fetch_row()[0]??"5000";
    $collected=$database->query("SELECT SUM(credits) FROM transactions where FROM_UNIXTIME(timestamp,'%m %y') = FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()),'%m %y')")->fetch_row()[0]??"0";
    $table=array("top"=>array(),"recent"=>array(),"goal"=>$mgoal,"collected"=>$collected);
    $cnt=0;
    while ($row=$topsupp->fetch_assoc()) {
        $user=getSteamData($row["steamid"]);
        $table["top"][$cnt]=array($user["name"],$row["SUM(credits)"],$row["steamid"],$user["avatarfull"]);
        $cnt++;
    }
    $cnt=0;
    while ($row=$recentdon->fetch_assoc()){
        $user=getSteamData($row["steamid"]);
        $table["recent"][$cnt]=array($user["name"],$row["credits"],$row["steamid"],$user["avatarfull"]);
        $cnt++;
    }
    echo json_encode($table);
}

if (isset($_GET["synch_ban"]))
{
    if ($settings["api_key"]!=$_GET["api_key"]){
        http_response_code(403);
		die(json_encode(array("error"=>"Access denied.")));
    }
    
    $sid=$_GET["synch_ban"];
    if (!isset($_POST["edited"])) {
        $admin=$_POST["admin"]??NULL;
        $type=$_POST["type"];
        $server=$_POST["server"];
        $reason=$_POST["reason"];
        $expires=$_POST["expires"];
        $database->query("INSERT INTO bans (type,offender_steamid,server,reason".(isset($admin)?",admin_steamid":"").",created".($expires>0?",expires":"").") VALUES ('$type','$sid','$server','$reason',".(isset($admin)?"'$admin',":"")."UNIX_TIMESTAMP(NOW())".($expires>0?",'$expires'":"").")");
	}elseif(!isset($_POST["unban"])){
        $reason=$_POST["reason"];
        $expires=$_POST["expires"];
        $type=$_POST["type"];
        $server=$_POST["server"];
        $database->query("UPDATE bans SET reason='$reason',expires='$expires' where offender_steamid='$sid' AND type='$type' AND server='$server' ORDER BY created DESC LIMIT 1");
	}else{
        $type=$_POST["type"];
        $server=$_POST["server"];
        $database->query("UPDATE bans SET expires=UNIX_TIMESTAMP(NOW()) where offender_steamid='$sid' AND type='$type' AND server='$server' ORDER BY created DESC LIMIT 1");
	}
    echo json_encode(array("success"=>"Ban synched successfully."));
}

//Notes
if (isset($_POST["get_tinydata"]))
{
    if (!hasAccess("notes")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $id=$_POST["get_tinydata"];
    $response=$database->query("SELECT * FROM notes WHERE id='$id'");
    if (mysqli_num_rows($response)){
        echo json_encode($response->fetch_array(MYSQLI_ASSOC))??"";
    };
}
if (isset($_POST["write_save"]))
{
    if (!hasAccess("notes")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $type=$_POST["write_save"];
    $headimg=$_POST['headimg']??NULL;
    $content=base64_encode($_POST["content"]);
    $title=$_POST["title"];
    $sid=$_SESSION["steamid"];
    $sql=$database->query("INSERT INTO notes (type,headimg,title,content,created,steamid) VALUES ('$type','$headimg','$title','$content',UNIX_TIMESTAMP(NOW()),'$sid')");
    if (!$sql){
        echo json_encode(array("error"=>"Ошибка: ".mysqli_error($database)));
    }else{
        echo json_encode(array("success"=>"Запись добавлена!"));
    };
}
if (isset($_POST["write_update"]))
{
    if (!hasAccess("notes")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $id=$_POST["write_update"];
    $headimg=$_POST['headimg']??NULL;
    $content=base64_encode($_POST["content"]);
    $title=empty($_POST["title"])?"title":$_POST["title"];
    $sql=$database->query("UPDATE notes SET headimg='$headimg',title='$title',content='$content' WHERE id='$id'");
    if (!$sql){
        echo json_encode(array("error"=>"Ошибка: ".mysqli_error($database)));
    }else{
        echo json_encode(array("success"=>"Запись обновлена!"));
    };
}
if (isset($_POST["get_notes"]))
{
    if (!hasAccess("notes")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $page=is_numeric($_POST["get_notes"])?(int)$_POST["get_notes"]:1;
    $limit=5;
    $start=($page-1)*$limit;
    $countres=$database->query("SELECT count(id) AS id FROM notes")??NULL;
    $fetchedcount=$countres->fetch_all(MYSQLI_ASSOC);
    $total=(int)$fetchedcount[0]["id"];
    $pages=(int)ceil($total/$limit);
    $prev=max(1,$page-1);
    $nxt=min($pages,$page+1);
    $response=$database->query("SELECT * FROM notes ORDER BY created DESC LIMIT $start, $limit;");
    if (mysqli_num_rows($response)){
        echo json_encode(["page"=>$page,"pages"=>$pages,"prev"=>$prev,"next"=>$nxt,"data"=>$response->fetch_all(MYSQLI_ASSOC)]);
    }
}
if(isset($_POST["nrm"])){
    if (!hasAccess("notes")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $id=$_POST["nrm"];
    $sql=$database->query("DELETE FROM notes WHERE id='$id'");
    if (!$sql){
        echo json_encode(array("error"=>"Ошибка: ".mysqli_error($database)));
    }else{
        echo json_encode(array("success"=>"Успешное удаление!"));
    };
}

//Globals
if (isset($_POST["settings_infoget"]))
{
    if (!hasAccess("global_settings")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $id=$_POST["settings_infoget"];
    $response=$database->query("SELECT value FROM global_settings WHERE name = '$id';");
    if (mysqli_num_rows($response)){
        echo json_encode($response->fetch_array()[0]??"");
    };
}
if(isset($_POST["settings_insert"])){
    if (!hasAccess("global_settings")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $name=$_POST["settings_insert"];
    $value=$_POST["value"];
    $response=$database->query("INSERT INTO global_settings (name,value) VALUES ('$name','$value') ON DUPLICATE KEY UPDATE value = '$value';");
}

//Servers
if (isset($_POST["get_servers"]))
{
    $response=$database->query("SELECT * FROM servers ORDER BY prior ASC;");
    if (mysqli_num_rows($response)){
        echo json_encode($response->fetch_all(MYSQLI_ASSOC))??"";
    };
}
if(isset($_POST["svrm"])){
    if (!hasAccess("servers")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $name=$_POST["svrm"];
    $sql=$database->query("DELETE FROM servers WHERE sv_name='$name'");
}
if (isset($_POST["svreorder"])){
    if (!hasAccess("servers")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $ids=json_decode($_POST["svreorder"],true);
    if (!is_array($ids)){
        http_response_code(400);
        echo json_encode(["error"=>"Invalid data."]);
        exit;
    }
    foreach($ids as $prior=>$id){
        $id=(int)$id;
        $prior=(int)$prior;
        $database->query("UPDATE servers SET prior=$prior WHERE id=$id");
    }
    echo json_encode(["success"=>true]);
    exit;
}
if (isset($_POST["svsave"]))
{
    if (!hasAccess("servers")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $name=$_POST["name"];
    $ip=$_POST["ip"];
    $port=$_POST["port"];
    $response=$database->query("INSERT INTO servers (sv_name,sv_ip,sv_port) VALUES ('$name','$ip','$port') ON DUPLICATE KEY UPDATE sv_name = '$name', sv_ip = '$ip', sv_port = '$port';");
}
// Storage moderation cards
if (isset($_POST["get_storage_cards"])){
    if (!hasAccess("storagemoderate")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $result=[];
    $dirs=glob("..".DIRECTORY_SEPARATOR."storage".DIRECTORY_SEPARATOR."*",GLOB_ONLYDIR+GLOB_NOSORT);
    foreach($dirs as $dir){
        $actualsid=basename($dir);
        if ($actualsid==$_SESSION["steamid"]) {continue;}
        $size=0;
        $cnt=0;
        foreach(new FilesystemIterator($dir) as $file){
            $size+=$file->getSize();
            $cnt++;
        }
        $fm_userdata=$GLOBALS["database"]->query("SELECT * FROM users WHERE steamid='$actualsid';")->fetch_assoc();
        $badchrs=['"',"'"];
        $result[]=[
            "steamid"=>$actualsid,
            "name"=>str_replace($badchrs,'',$fm_userdata['name']),
            "avatarfull"=>$fm_userdata["avatarfull"],
            "rank"=>$settings["ranks"][$actualsid]??"User",
            "size"=>$size,
            "cnt"=>$cnt
        ];
    }
    echo json_encode($result);
    exit;
}
// User management
if (isset($_POST["user_management"])){
    if (!hasAccess("user_management")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $mtype=$_POST["user_management"];
    if ($mtype=="setrank"){
        $sid=$_POST["steamid"];
        $id=$_POST["id"];
        $id=$id===""?"NULL":(int)$id;
        $response=$database->query("UPDATE users SET ugroup=$id WHERE steamid='$sid'");
    }
    if (!$response){
        http_response_code(500);
        echo json_encode(["error"=>"Ошибка: ".$database->error]);
    } else {
        header("Location: ".($_SERVER["HTTP_REFERER"]));
    }
}
// Forum
function forum_strVal($content,$min=44,$max=40000){
    if (mb_strlen(trim(str_replace("\n","",$content)),"UTF-8")>$max||mb_strlen(trim(str_replace("\n","",$content),"UTF-8"))<$min){
        return false;
    }else{
        return true;
    }
}
function isSubcatLocked($subcatid){
    $subcatCheck=$GLOBALS["database"]->query("SELECT locked FROM forum_subcats WHERE id = ".$subcatid);
    if ($subcatCheck&&$subcatCheck->num_rows>0){
        $subcat=$subcatCheck->fetch_assoc();
        if ($subcat["locked"]==1){
            return true;
        }else{
            return false;
        }
    }
}

if (isset($_POST["forum"])){
    
    $action=$_POST["forum"];

    if($action==="new_thread"){        
        $subcat_id=intval($_POST["subcat_id"]??0);
        $topic=$_POST["topic"]??"";
        $content=$_POST["content"]??"";
        $sid=$_SESSION["steamid"];

        if (!$sid){
            http_response_code(403);
            echo json_encode(["error"=>"Access denied."]);
            exit;
        }

        if (isSubcatLocked($subcat_id)&&!hasAccess("forum_admin")){
            echo json_encode(["error"=>"Сабкатегория закрыта для дальнейших постов."]);
            exit;
        }

        if (!forum_strVal($content)||!forum_strVal($topic,1,64)) {
            echo json_encode(["error"=>"Тема либо содержание поста слишком коротки."]);
            exit;
        }

        $res=$database->query("SELECT timestamp FROM forum_threads WHERE sid='$sid' ORDER BY timestamp DESC LIMIT 1");
        if($res && $row=$res->fetch_assoc()){
            if(time()-$row["timestamp"]<600){
                echo json_encode(["error"=>"Вы можете создавать новый тред не чаще чем раз в 10 минут."]);
                exit;
            }
        }

        $database->query("INSERT INTO forum_threads (subcat_id, sid, topic, timestamp, last_posted) VALUES ($subcat_id, '$sid', '".$database->real_escape_string($topic)."', UNIX_TIMESTAMP(), UNIX_TIMESTAMP())");
        $thread_id=$database->insert_id;
        $database->query("INSERT INTO forum_posts (thread_id, sid, content, timestamp) VALUES ($thread_id, '$sid', '".$database->real_escape_string($content)."', UNIX_TIMESTAMP())");

        echo json_encode(["success"=>true]);
        exit;
    }elseif ($action==="get_threads"){
        $subcat_id=intval($_POST["subcat_id"]??0);
        $page=max(intval($_POST["page"]??1),1);
        $perPage=8;
        $offset=($page-1)*$perPage;

        $cntQ=$database->query("SELECT COUNT(*) as cnt FROM forum_threads WHERE subcat_id = $subcat_id");
        $cnt=$cntQ->fetch_assoc()["cnt"]??0;
        $pages=ceil($cnt/$perPage);
        $threadsQ = $database->query("
            SELECT t.id, t.topic,
                   FROM_UNIXTIME(t.timestamp,'%d.%m.%Y') AS created,
                   u.name AS author_name,
                   u.avatarfull AS author_avatar,
                   u.steamid AS author_steamid,
                   (SELECT COUNT(*)-1 FROM forum_posts p WHERE p.thread_id = t.id) AS replies,
                   t.pinned
            FROM forum_threads t
            JOIN users u ON u.steamid = t.sid
            WHERE t.subcat_id = $subcat_id
            ORDER BY t.pinned DESC, t.last_posted DESC
            LIMIT $offset, $perPage
        ");

        $data=[];
        while ($row=$threadsQ->fetch_assoc()) {
            $data[]=$row;
        }

        echo json_encode([
            "success"=>true,
            "data"=>$data,
            "page"=>$page,
            "pages"=>$pages,
            "prev"=>max(1,$page-1),
            "next"=>min($pages,$page+1)
        ]);
        exit;
    }elseif ($action==="new_post"){
        $thread_id=intval($_POST["thread_id"]??0);
        $content=$_POST["content"]??"";
        $reply_id=isset($_POST["reply_id"])&&$_POST["reply_id"]!==""?intval($_POST["reply_id"]):"NULL";
        $sid=$_SESSION["steamid"];

        if (!$sid){
            http_response_code(403);
            echo json_encode(["error"=>"Access denied."]);
            exit;
        }elseif(getUserGroup()==5){
            http_response_code(403);
            echo json_encode(["error"=>"Вы были забанены."]);
            exit;
        }

        if (!forum_strVal($content,26)){
            echo json_encode(["error"=>"Содержание поста слишком короткое/длинное."]);
            exit;
        }

        $res=$database->query("SELECT timestamp FROM forum_posts WHERE sid='$sid' ORDER BY timestamp DESC LIMIT 1");
        if($res && $row=$res->fetch_assoc()){
            if(time()-$row["timestamp"]<60){
                echo json_encode(["error"=>"Вы можете создавать новый пост не чаще чем раз в минуту."]);
                exit;
            }
        }
    
        $checkQ=$database->query("SELECT locked,subcat_id FROM forum_threads WHERE id = $thread_id");
        if ($checkQ->num_rows===0){
            echo json_encode(["error"=>"Тред не найден"]); 
            exit;
        }
        $threadData=$checkQ->fetch_assoc();
        if (($threadData["locked"]==1||isSubcatLocked($threadData["subcat_id"]))&&!hasAccess("forum_admin")){
            echo json_encode(["error"=>"Тред закрыт для дальнейших постов."]);
            exit; 
        }

        if ($reply_id!=="NULL"){
            $replyCheck=$database->query("SELECT id, thread_id FROM forum_posts WHERE id = $reply_id");
            if ($replyCheck->num_rows===0) {
                echo json_encode(["error"=>"Ответный пост не найден"]);
                exit;
            }
            $replyData=$replyCheck->fetch_assoc();
            if ($replyData["thread_id"]!=$thread_id) {
                echo json_encode(["error"=>"Вы не можете ответить на пост из другого треда."]);
                exit;
            }
        }
    
        $database->query("INSERT INTO forum_posts (thread_id, sid, content, timestamp, isreplyto) VALUES ($thread_id,'$sid','".$database->real_escape_string($content)."',UNIX_TIMESTAMP(),".($reply_id==="NULL"?"NULL":$reply_id).")");

        $newPostId=$database->insert_id;
    
        $database->query("UPDATE forum_threads SET last_posted = UNIX_TIMESTAMP(), last_post_sid = '$sid' WHERE id = $thread_id");

        $countRes=$database->query("SELECT COUNT(*) AS total_posts FROM forum_posts WHERE thread_id = $thread_id");
        $totalPosts=$countRes?intval($countRes->fetch_assoc()["total_posts"]):0;

        echo json_encode([
            "success"=>true,
            "thread"=>[
                "total_posts"=>$totalPosts,
                "last_post_id"=>$newPostId
            ]
        ]);
        exit;
    }elseif ($action==="edit_post") {
        $post_id=intval($_POST["post_id"]??0);
        $content=$_POST["content"]??"";
        $sid=$_SESSION["steamid"];

        if (!$sid) {
            http_response_code(403);
            echo json_encode(["error"=>"Access denied."]);
            exit;
        } elseif (getUserGroup()==5) {
            http_response_code(403);
            echo json_encode(["error"=>"Вы были забанены."]);
            exit;
        }

        if (!forum_strVal($content,26)) {
            echo json_encode(["error"=>"Содержание поста слишком короткое/длинное."]);
            exit;
        }

        $checkQ=$database->query("SELECT sid, thread_id FROM forum_posts WHERE id = $post_id LIMIT 1");
        if ($checkQ->num_rows===0) {
            echo json_encode(["error"=>"Пост не найден."]);
            exit;
        }

        $postData=$checkQ->fetch_assoc();
        $author_sid=$postData["sid"];
        $thread_id=intval($postData["thread_id"]);

        if ($author_sid!==$sid&&!hasAccess("forum_admin")) {
            http_response_code(403);
            echo json_encode(["error"=>"У вас нет прав для редактирования этого поста."]);
            exit;
        }

        $threadQ=$database->query("SELECT locked,subcat_id FROM forum_threads WHERE id = $thread_id");
        $threadData=$threadQ->fetch_assoc();
        if ($threadQ&&$threadQ->num_rows>0&&($threadData["locked"]==1||isSubcatLocked($threadData["subcat_id"]))&&!hasAccess("forum_admin")){
            echo json_encode(["error"=>"Тред закрыт для редактирования постов."]);
            exit;
        }

        $escapedContent=$database->real_escape_string($content);
        $database->query("UPDATE forum_posts SET content = '$escapedContent', edited = UNIX_TIMESTAMP() WHERE id = $post_id");

        echo json_encode(["success"=>true]);
        exit;
    }elseif ($action==="reaction"){
        $post_id=intval($_POST["post_id"]??0);
        $type=$_POST["type"]??"";
        $sid=$_SESSION["steamid"];

        if (!$sid){
            http_response_code(403);
            echo json_encode(["error"=>"Войдите чтобы оставлять реакции."]);
            exit;
        }elseif(getUserGroup()==5){
            http_response_code(403);
            echo json_encode(["error"=>"Вы были забанены."]);
            exit;
        }

        $postQ=$database->query("SELECT sid FROM forum_posts WHERE id=$post_id");
        if (!$postQ||$postQ->num_rows===0) {
            echo json_encode(["error"=>"Пост не найден."]);
            exit;
        }

        $postOwner=$postQ->fetch_assoc()["sid"];
        if ($postOwner===$sid) {
            echo json_encode(["error"=>"Реакции самому себе запрещены."]);
            exit;
        }

        $allowed=["love","funny","wow","sad","trash"];

        if (!in_array($type,$allowed,true)){
            http_response_code(400);
            echo json_encode(["error"=>"Invalid reaction type"]);
            exit;
        }

        $checkQ=$database->query("SELECT id FROM forum_reactions WHERE post_id=$post_id AND steamid='$sid' AND reaction_type='$type'");
        if ($checkQ->num_rows>0){
            $database->query("DELETE FROM forum_reactions WHERE post_id=$post_id AND steamid='$sid' AND reaction_type='$type'");
            $removed=true;
            $added=false;
        } else {
            $database->query("INSERT INTO forum_reactions (post_id, steamid, reaction_type, timestamp) VALUES ($post_id, '$sid', '$type', UNIX_TIMESTAMP())");
            $added=true;
            $removed=false;
        }

        $cntQ=$database->query("SELECT COUNT(*) as cnt FROM forum_reactions WHERE post_id=$post_id AND reaction_type='".$database->real_escape_string($type)."'");
        $count=$cntQ->fetch_assoc()["cnt"]??0;

        echo json_encode([
            "success"=>true,
            "added"=>$added,
            "removed"=>$removed,
            "count"=>$count
        ]);

        exit;
    }
}
if (isset($_POST["forum_admin"])){
    if (!hasAccess("forum_admin")){
        http_response_code(403);
        echo json_encode(["error"=>"Access denied."]);
        exit;
    }
    $action=$_POST["forum_admin"];
    $response=false;
    if($action==="new_cat"){
        $name=$database->real_escape_string($_POST["name"]);
        $prior=(int)$_POST["prior"];
        $response=$database->query("INSERT INTO forum_cats (name,prior) VALUES ('$name','$prior')");
    }elseif($action==="new_subcat") {
        $name=$database->real_escape_string($_POST["name"]);
        $prior=(int)$_POST["prior"];
        $cat_id=(int)$_POST["cat_id"];
        $icon=$database->real_escape_string($_POST["icon"]);
        $response=$database->query("INSERT INTO forum_subcats (name,prior,icon,cat_id) VALUES ('$name','$prior','$icon','$cat_id')");
    }elseif($action==="delete_cat") {
        $id=(int)$_POST["id"];
        $response=$database->query("DELETE FROM forum_cats WHERE id='$id'");
    }elseif($action==="delete_subcat"){
        $id=(int)$_POST["id"];
        $response=$database->query("DELETE FROM forum_subcats WHERE id='$id'");
    }elseif($action==="delete_post"){
        $id=(int)$_POST["id"];
        $response=$database->query("DELETE FROM forum_posts WHERE id='$id'");
    }elseif($action==="delete_thread"){
        $id=(int)$_POST["id"];
        $response=$database->query("DELETE FROM forum_threads WHERE id='$id'");
    }elseif($action==="pin_thread"){
        $id=(int)$_POST["id"];
        $row=$database->query("SELECT pinned FROM forum_threads WHERE id='$id' LIMIT 1")->fetch_assoc();
        $pinned=$row["pinned"]==1?"NULL":1;
        $response=$database->query("UPDATE forum_threads SET pinned=$pinned WHERE id='$id'");
    }elseif($action==="lock_thread"){
        $id=(int)$_POST["id"];
        $row=$database->query("SELECT locked FROM forum_threads WHERE id='$id' LIMIT 1")->fetch_assoc();
        $locked=$row["locked"]==1?"NULL":1;
        $response=$database->query("UPDATE forum_threads SET locked=$locked WHERE id='$id'");
    }elseif($action==="lock_subcat"){
        $id=(int)$_POST["id"];
        $row=$database->query("SELECT locked FROM forum_subcats WHERE id='$id' LIMIT 1")->fetch_assoc();
        $locked=$row["locked"]==1?"NULL":1;
        $response=$database->query("UPDATE forum_subcats SET locked=$locked WHERE id='$id'");
    }elseif($action==="edit_cat"){
        $id=(int)$_POST["id"];
        $name=$database->real_escape_string($_POST["name"]);
        $response=$database->query("UPDATE forum_cats SET name='$name' WHERE id='$id'");
    }elseif($action==="edit_subcat"){
        $id=(int)$_POST["id"];
        $name=$database->real_escape_string($_POST["name"]);
        $cat_id=(int)$_POST["cat_id"];
        $icon=$database->real_escape_string($_POST["icon"]);
        $response=$database->query("UPDATE forum_subcats SET name='$name',cat_id='$cat_id',icon='$icon' WHERE id='$id'");
    }elseif($action==="move_thread"){
        $thread_id=(int)$_POST["thread_id"];
        $subcat_id=(int)$_POST["subcat_id"];
        $response=$database->query("UPDATE forum_threads SET subcat_id='$subcat_id' WHERE id='$thread_id'");
    }elseif($action==="move_subcat"){
        $subcat_id=(int)$_POST["subcat_id"];
        $cat_id=(int)$_POST["cat_id"];
        $response=$database->query("UPDATE forum_subcats SET cat_id='$cat_id' WHERE id='$subcat_id'");
    }elseif($action==="swap_cat_prior"){
        $cat_id=(int)$_POST["cat_id"];
        $target_id=(int)$_POST["target_cat_id"];
        $a=$database->query("SELECT prior FROM forum_cats WHERE id='$cat_id' LIMIT 1")->fetch_assoc()["prior"];
        $b=$database->query("SELECT prior FROM forum_cats WHERE id='$target_id' LIMIT 1")->fetch_assoc()["prior"];
        $database->query("UPDATE forum_cats SET prior='$b' WHERE id='$cat_id'");
        $response=$database->query("UPDATE forum_cats SET prior='$a' WHERE id='$target_id'");
    }elseif($action==="swap_subcat_prior"){
        $subcat_id=(int)$_POST["subcat_id"];
        $target_id=(int)$_POST["target_subcat_id"];
        $a=$database->query("SELECT prior FROM forum_subcats WHERE id='$subcat_id' LIMIT 1")->fetch_assoc()["prior"];
        $b=$database->query("SELECT prior FROM forum_subcats WHERE id='$target_id' LIMIT 1")->fetch_assoc()["prior"];
        $database->query("UPDATE forum_subcats SET prior='$b' WHERE id='$subcat_id'");
        $response=$database->query("UPDATE forum_subcats SET prior='$a' WHERE id='$target_id'");
    }
    if (!$response){
        http_response_code(500);
        echo json_encode(["error"=>"Ошибка: ".$database->error]);
    } else {
        echo json_encode(["success"=>true]);
    }
    exit;
}
?>