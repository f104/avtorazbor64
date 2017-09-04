{extends '_base.tpl'}

{block 'content'}
  <table class="table">
    <thead>
      <th>Склад</th>
      <th>Позиций<br><small>всего / с ошибками / без фото</small></th>
      <th>Изображений<br><small>всего / обработано</small></th>
      <th>Дата/время обновления</th>
    </thead>
    <tbody>
      {foreach $sklads as $sklad}
        <tr>
          <td>{$sklad.prefix}</td>
          <td>
            <span class="text-nowrap">{$sklad.items_total} / 
              {if $sklad.items_unpublished?}
                <a href="#" class="toggler" data-toggle=".js-{$sklad.prefix}-unpublished">{$sklad.items_unpublished}</a>
              {else}
                {$sklad.items_unpublished}
              {/if}
              / 
              {if $sklad.items_without_images?}
                <a href="#" class="toggler" data-toggle=".js-{$sklad.prefix}-without_images">{$sklad.items_without_images}</a>
              {else}
                {$sklad.items_without_images}
              {/if}
            </span>
            {if $sklad.items_unpublished_list?}
              <ul class="hidden toggler-content js-{$sklad.prefix}-unpublished" style="position:absolute">
                {foreach $sklad.items_unpublished_list as $item}
                  <li>{$item}</li>
                {/foreach}
                <a href="#" class="toggler" data-toggle=".js-{$sklad.prefix}-unpublished">Закрыть</a>
              </ul>
            {/if}
            {if $sklad.items_without_images_list?}
              <ul class="hidden toggler-content js-{$sklad.prefix}-without_images" style="position:absolute">
                {foreach $sklad.items_without_images_list as $item}
                  <li><a href="/?code={$item}" target="_blank">{$item}</a></li>
                {/foreach}
                <a href="#" class="toggler" data-toggle=".js-{$sklad.prefix}-without_images">Закрыть</a>
              </ul>
            {/if}
          </td>
          <td><span class="text-nowrap">
            {$sklad.images_total} / {$sklad.images_prepared}
            {if $sklad.images_total != 0}
              ({$.php.round($sklad.images_prepared * 100 / $sklad.images_total, 2)}%)
            {/if}
          </span></td>
          <td>{if $sklad.updatedon != '0000-00-00 00:00:00'}{$sklad.updatedon|date:"d-m-Y H:i"}{/if}</td>
        </tr>
      {/foreach}
    </tbody>
  </table>
{/block}