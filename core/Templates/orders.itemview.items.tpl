<ul class="items-table">
    <li class="items-table_header">
      <div></div>
      <div>Информация</div>
      <div>Цена</div>
      <div>&nbsp;</div>
    </li>
    {foreach $items as $item}
      <li>
        <div>
          <div class="popup-gallery js-popup-gallery">
            {if $item.images}
              {foreach $item.images as $image}
                {if $image.filename?}
                  <a href="assets/images/data/{$image.prefix}/{$image.filename}" title="{$item.name|e} ({$image.item_key}/{$image.prefix})"><img src="assets/images/data/{$image.prefix}/120x90/{$image.filename}" alt="{$item.name|e}"></a>
                {else}
                  {*remote file*}
                  <a href="{$image.url}" title="{$item.name|e} ({$image.item_key}/{$image.prefix})"><img src="{$image.url}" alt="{$item.name|e}"></a>
                {/if}
              {/foreach}
              {set $imagesCount = $item.images|count}
              {if $imagesCount > 1}
                <span>+{$imagesCount-1}</span>
              {/if}
            {else}
              <img src="assets/images/nophoto.png"">
            {/if}
          </div>
        </div>
        <div>
          <h3>{$item.name}</h3>
          <p class="small">код: {$item.code}, склад: {$item.prefix}</p>
          <!-- {$item.id} -->
        </div>
        <div>
          <p><span class="items-table-hidden">Цена: </span><span class="price">{$item.price} <i class="fa fa-rub"></i></span></p>
        </div>
        <div>
          <form action="orders/itemview/replaceitem" class="js-ajaxform">
            <input type="hidden" name="id" value="{$order.id}">
            <input type="hidden" name="new_item" value="{$item.id}">
            <button class="btn btn-primary" {if $order.item_id == $item.id}disabled{/if}>Выбрать</button>
          </form>
        </div>
      </li>
    {/foreach}
  </ul>