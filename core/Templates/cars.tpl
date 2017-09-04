{extends '_base.tpl'}

{block 'content'}
  <div class="row cars-row">
    <div class="col-sm-9">
      {include '_cars.content.tpl'}
      {include '_cars.requestPhoto.form.tpl'}
    </div>  
    <div class="col-sm-3">
      {if $show == 'marks'}
        <div class="total-promo wow bounceIn" data-wow-delay=".3s">
          <big>{$totalItems}</big>
          {$totalItems | decl : 'деталь,детали,деталей'} в&nbsp;наличии с&nbsp;доставкой по&nbsp;всей России
        </div>
        <div class="invite-promo">
          Предлагаем всем желающим стать поставщиком и&nbsp;разместить свои&nbsp;товарные остатки на&nbsp;нашем&nbsp;сайте
          <a href="invite">Узнать подробности</a>
        </div>
      {/if}
      <div class="well">
        <p>Детали расположены на разных складах в России и СНГ, обращайте внимание на срок доставки до вашего представительства и стоимость детали. Они указаны в соответствующих полях при выводе результатов поиска конкретной детали.</p>
        <p>Представительства:</p>
        <ul>
          <li>Московская обл., г. Мытищи, <span class="text-nowrap">ул. 3-я Новая, 30</span></li>
          <li>г. Саратов, <span class="text-nowrap">ул. Танкистов, 90А</span></li>
          <li>г. Уфа, <span class="text-nowrap">ул. Машиностроителей, 21/1</span></li>
          <li>г. Пенза, <span class="text-nowrap">проспект Победы, 140</span></li>
          <li>г. Ульяновск, <span class="text-nowrap">Московское шоссе, 80а</span></li>
        </ul>
        <p>Если в вашем городе нет представильства, срок доставки указан до нашего склада в Москве. 
          Без регистрации на сайте стоимость детали также указана на нашем складе в Москве.</p>
        {*<p>Предлагаем всем желающим стать поставщиком и разместить свои товарные остатки на нашем сайте. Более подробные данные в разделе поставщикам.</p>*}
        
{*        <p><strong>Срочный выкуп аварийных, неисправных, горелых и&nbsp;прочих авто <a href="tel:+79626255555">+79626255555</a></strong></p>*}
      </div>
{*      <div class="ads-right"><img src="assets/images/avgparts.jpg" width="270"></div>*}
      <div class="ads-right">
        <script async='async' src='https://www.googletagservices.com/tag/js/gpt.js'></script>
        <script>
          var googletag = googletag || {};
          googletag.cmd = googletag.cmd || [];
        </script>

        <script>
          googletag.cmd.push(function() {
            googletag.defineSlot('/18106867/avangard.market_right', [270, 225], 'div-gpt-ad-1483623129641-0').addService(googletag.pubads());
            googletag.pubads().enableSingleRequest();
            googletag.enableServices();
          });
        </script>
        <!-- /18106867/avangard.market_right -->
        <div id='div-gpt-ad-1483623129641-0' style='height:225px; width:270px;'>
        <script>
        googletag.cmd.push(function() { googletag.display('div-gpt-ad-1483623129641-0'); });
        </script>
        </div>
      </div>
    </div>
  </div>  
{/block}