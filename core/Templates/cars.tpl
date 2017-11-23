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
          {$totalItems | decl : 'деталь,детали,деталей'} в&nbsp;наличии на&nbsp;складе в&nbsp;Саратове. Отправка во&nbsp;все регионы России и&nbsp;СНГ.
        </div>
      {/if}
      <div class="well">
        <p>Все детали, размещенные в каталоге, имеются в наличии и расположены на складе в Саратове по адресу ул. Азина, 50.
          Дополнительная точка выдачи расположена по адресу г. Саратов, ул. Танкистов, 90А.</p>
        <p>Если Вам необходимо переместить товар в точку выдачи или отправить в другой 
          регион, сяжитесь с нашими менеджерами и они пояснят, как это реализовать. <a href="info">Подробности здесь.</a></p>
      </div>
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