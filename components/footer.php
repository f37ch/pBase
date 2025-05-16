</main>
<footer class="mt-auto text-white pt-4">
        <p>developed by <a href="https://github.com/f37ch" class="text-white">@f37ch</a> • <a href="<?=getSetting("tos",false)??"/"?>" class="text-white">TOS</a></p>
        
      </footer>
    </div>
    <script src="<?=asset_version("/js/bootstrap.bundle.min.js")?>"></script>
    <script src="<?=asset_version("/js/aos.js")?>"></script>
    <script type="text/javascript">
      AOS.init();
      //window.onscroll=function(){AOS.refresh()};
      window.onclick=function(){setTimeout(AOS.refresh,300);};
    </script>
  </body>
</html>