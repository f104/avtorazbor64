<ul class="breadcrumb">
  {foreach $items as $item}
    {if !$item.active}
      <li><a href="{$item.uri}">{$item.title}</a></li>
    {else}
      <li>{$item.title}</li>
    {/if}
  {/foreach}
</ul>