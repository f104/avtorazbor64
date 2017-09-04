<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{block 'title'}{$pagetitle} / Авторазбор Авангард{/block}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="{$_core->siteUrl}">
    {block 'css'}
      {if $_core->useMunee}
        <link rel="stylesheet" href="/assets/css/common.css">
      {else}
        <link rel="stylesheet" href="/assets/css/common.css">
      {/if}
    {/block}
  </head>
  <body>
    {block 'content'}
    <div class="main">
      <div class="container-fluid">
        <div class="row sticker-wrapper">
          <div class="sticker col-sm-6 text-center">
            <div class="well well-sm">
              <p><strong>{$item->name}</strong></p>
              <p><img src="data:image/png;base64,{$barcodeBase64}"></p>
              <p>{$item->code}</p>
            </div>
          </div>
        </div>
        <button class="btn btn-primary hidden-print" onclick="window.print()">Печать</button>
        <button class="btn btn-default hidden-print js-duplicate-sticker">Дублировать стикер</button>
        <button class="btn btn-default hidden-print" onclick="window.close()">Закрыть окно</button>
      </div>
    </div>
    {/block}            
    {block 'js'}
      {if $_core->useMunee}
      <script src="/assets/js/jquery-2.1.4.min.js,/assets/js/sticker.js"></script>
      {else}
      <script src="/assets/js/jquery-2.1.4.min.js"></script>
      <script src="/assets/js/sticker.js"></script>
      {/if} 
    {/block}
  </body>
</html>