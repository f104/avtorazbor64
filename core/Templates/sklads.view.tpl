{* Шаблон просмотра/рекдатирования склада *}

{extends '_base.tpl'}

{block 'content'}
  
<div class="row">
  <div class="col-sm-6">
    {if $statusMessage}
      <p class="text-warning">{$statusMessage}</p>
    {/if}  
    {$formEdit}
    {$formDelete}
  </div>
  <div class="col-sm-6">
    <p>Товаров на складе: 
      {if $totalItems != 0}
        <a href="items?sklad_id={$sklad.id}">{$totalItems}</a><br>
        из них показываются: {$totalItemsShow}</p>
      {else}{$totalItems}{/if}
    <p>Последнее обновление: {if $sklad.updatedon != '0000-00-00 00:00:00'}{$sklad.updatedon|date:"d-m-Y H:i"}{/if}</p>
  </div>
</div>
    
{/block}