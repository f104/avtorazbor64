{extends '_base.tpl'}

{block 'content'}
{if $allowReplace}
  <div class="row">
    <div class="col-sm-4">
      <p>Вы можете заменить товар в&nbsp;заказе на&nbsp;другой одобренный и&nbsp;опубликованный товар с&nbsp;одобренного и&nbsp;включенного склада.</p>
      {$formCCE}
    </div>
    <div class="col-sm-8 js-items-wrapper">
      {$items}
    </div>
  </div>
{else}
  <p>{$lang['status_error']}</p>
{/if}
{/block}