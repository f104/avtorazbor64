{* описание статусов *}
<p><a href="#" class="toggler" data-toggle=".js-statuses-help">Описание статусов</a></p>
<div class="hidden toggler-content js-statuses-help">
  <dl class="dl-horizontal">
    {foreach $statuses as $item}
      <dt><span class="supplier-status-{$item.id}">{$item.name}</span></dt>
      <dd>{$item.description}</dd>
    {/foreach}
  </dl>
</div>