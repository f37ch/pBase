<header class="mb-auto pb-4">
    <div>
      <h3 class="mb-0 font_big" id="project_name"><?php echo getSetting("project_name",false)??"pBase"?></h3>
      <nav class="nav nav-masthead justify-content-center">
        <a class="<?=getPage("home");?>" href="/">Главная</a>
        <?php if (getSetting("enable_news",true)){?>
          <a class="<?=getPage("news");?>" href="/news.php">Новости</a>
        <?php }?>
        <?php if (getSetting("enable_help",true)){?>
          <a class="<?=getPage("help");?>" href="/help.php">Помощь</a>
        <?php }?>
        <?php if (getSetting("enable_banlist",true)){?>
          <a class="<?=getPage("bans");?>" href="/bans.php">Баны</a>
        <?php }?>
        <?php if (isset($_SESSION["steamid"])){?>
          <a class="<?=getPage("profile");?>" href="/profile.php">Профиль</a>
          <?php }else{?>
          <a class="<?=getPage("profile");?>" href="?login">Войти</a>
        <?php }?>
      </nav>
    </div>
</header>
<main>