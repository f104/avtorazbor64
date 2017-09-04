{extends '_base.tpl'}

{block 'content'}
  {$content}
  <div class="clearfix">
    <p class="pull-left"><img src="data:image/png;base64,{$barcodeBase64}"></p>
    <p class="pull-right"><button class="btn btn-primary hidden-print" onclick="window.print()">{$lang['print']}</button></p>
  </div>
{/block}