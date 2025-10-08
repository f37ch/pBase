<?php include("head.php") ?>
<?php $_GET["page"]="donate"; ?>
<?php include("header.php");
  $collected=$database->query("SELECT SUM(credits) FROM transactions where FROM_UNIXTIME(timestamp,'%m %y') = FROM_UNIXTIME(UNIX_TIMESTAMP(NOW()),'%m %y')")->fetch_row()[0]??"0";
  $mgoal=getSetting("donate_goal",false)??"5000";
  $mgoaltxt=getSetting("goal_text",false)??"monthly goal";
  $currency=getSetting("donate_currency",false)??"RUB";
  $percentage = ceil($collected/$mgoal*100);
  $svq=$database->query("SELECT * FROM servers;");

  $topsupp=$database->query("SELECT steamid,SUM(credits) FROM transactions GROUP BY steamid ORDER BY SUM(credits) DESC LIMIT 10");
  $recentdon=$database->query("SELECT * FROM transactions ORDER BY id DESC LIMIT 10");
?>
  <div data-aos="zoom-out" data-aos-delay="100">
    <h3 style="text-align:left!important;"><?php echo $mgoaltxt.":"?><small style="color: rgb(94, 197, 130);">&nbsp;<?php echo $collected."/".$mgoal.  $currency?></small><h3>
    <div class="progress" role="progressbar" style="height: 10.6px;" aria-label="Success striped example" aria-valuenow="25" aria-valuemin="0"  aria-valuemax="100">
      <div class="progress-bar progress-bar-striped bg-success progress-bar-animated" style="width: <?php echo $percentage?>%;"></div>
    </div>
    <form class="input-group input-group mt-3" action="methods.php" method="get">
      <button type="submit" onclick="" class="btn btn-secondary bg-white">Пополнить</button>
      <?php  if (isset($_SESSION["steamid"])) {
        echo "<input type='hidden' name='sid' value='".$_SESSION["steamid"]."'/>";
      }?>
      <select class="form-select shadow-none" name="svid" style="width: 20px">
        <?php while ($row=$svq->fetch_assoc()):?>
          <option value="<?php echo $row["id"];?>" <?php echo (isset($_GET["sv"])&&$_GET["sv"]==$row["sv_name"])?"selected":""; ?>><?php echo $row["sv_name"]?></option>
        <?php endwhile; ?>
      </select>
      <input type="number" name="amount" class="form-control shadow-none" value="5" min="5" required> </input>
      <span class="input-group-text"><?php echo $currency?></span>
    </form>
  </div>
  <div style="overflow-x: hidden;" class="mt-4">
    <div class="row d-flex flex-wrap">
      <div class="col-12 col-md-6" data-aos="fade-right" data-aos-delay="100">
        <h3 class="text-center fw-bold">ТОП ДОНАТЕРОВ</h3>
        <div class="table-responsive">
          <table class="table table-bordered table-light table-striped table-sm" style="table-layout:fixed;">
              <tbody>
                <?php while ($row=$topsupp->fetch_assoc()):?>
                <?php $user=getSteamData($row["steamid"]);?>
                  <tr>
                    <td class="align-middle text-start" style="width:68%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                      <a href="/profile.php?id=<?=$row["steamid"]?>" class="d-inline-block fs-4 text-black" style="text-decoration: none;">
                        <img src="<?php echo $user["avatarfull"]?>" style="width:41px;height:41px; border-radius:50%;"> <?=htmlspecialchars($user["name"],ENT_QUOTES,"UTF-8")?></a>
                    </td>
                    <td class="align-middle">
                      <h5 class="my-0"><?php echo $row["SUM(credits)"].$currency;?></h5>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
          </table>
        </div>
      </div>
      <div class="col-12 col-md-6" data-aos="fade-left" data-aos-delay="100">
        <h3 class="text-center fw-bold">НЕДАВНИЕ ДОНАТЫ</h3> 
        <div class="table-responsive">
          <table class="table table-bordered table-light table-striped table-sm" style="table-layout:fixed;">
              <tbody>
                <?php while ($row=$recentdon->fetch_assoc()):?>
                  <?php $user=getSteamData($row["steamid"]);?>
                  <tr>
                    <td class="align-middle text-start" style="width:68%;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;">
                      <a href="/profile.php?id=<?=$row["steamid"]?>" class="d-inline-block fs-4 text-black" style="text-decoration: none;">
                        <img src="<?php echo $user["avatarfull"]?>" style="width:41px;height:41px; border-radius:50%;"> <?=htmlspecialchars($user["name"],ENT_QUOTES,"UTF-8")?></a>
                    </td>
                    <td class="align-middle">
                      <h5 class="my-0"><?php echo $row["credits"].$currency;?></h5>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
          </table>
        </div>
      </div>
    <div>
  </div>
<?php include("footer.php") ?>