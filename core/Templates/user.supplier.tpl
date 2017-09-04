<p><a href="#" class="toggler" data-toggle=".js-statuses-help">Статус поставщика</a> <span class="supplier-status-{$status.id}">{$status.name}</span></p>
<div class="hidden toggler-content js-statuses-help">
  <dl class="dl-horizontal">
    {foreach $statuses as $item}
        <dt><span class="supplier-status-{$item.id}">{$item.name}</span></dt>
        <dd>{$item.description}</dd>
    {/foreach}     
  </dl>
</div>
{if $supplier.status_message?}<p class="alert alert-info">{$supplier.status_message}</p>{/if}

<p>Уникальный код (ID) поставщика: {$supplier.id}</p>

{if $neworders?}
  <p>У вас <a href="orders"><strong>{$neworders|decl:'новый заказ,новых заказа,новых заказов':true}</strong></a></p>  
{/if}

{if $supplier.status_id == 1}
  <p class="alert alert-info">Для продолжения работы вам необходимо заполнить <a href="/user/supplier/info">анкету поставщика.</a></p>
{/if}