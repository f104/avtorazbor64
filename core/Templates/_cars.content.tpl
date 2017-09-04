{if $items}
  {*$breadcrumbs*}
  {$selects}

  {switch $show}
  {case 'items'}
  <ul class="items-table">
    <li class="items-table_header">
      <div></div>
      <div>Информация</div>
      <div>
        Цена
        {if $showPrice and $_controller->total > 1}
          <a class="link-clear" href="{$_controller->makeSortUrl()}&sortby=price"><i class="fa fa-sort"></i></a>
        {/if}
      </div>
      {if $showPrice}
        <div>Средний&nbsp;срок доставки</div>
        <div>&nbsp;</div>
      {/if}
    </li>
    {foreach $items as $item}
      <li>
        <div>
          {if $item.images}
            <div class="popup-gallery js-popup-gallery">
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
            </div>
          {else}
            <div class="item-nophoto"><img src="assets/images/nophoto.png">{if $item.source == '1C'}<button class="btn btn-sm btn-primary js-button-requestphoto" data-id="{$item.id}">Запросить фото</button>{/if}</div>
          {/if}
        </div>
        <div>
          <h3><a href="cars/item?id={$item.id}">{$item.name}</a></h3>
          <p class="small">код: {$item.code}, склад: {$item.country_iso} {$item.prefix}{if $item.remote_id?}, артикул: {$item.remote_id}{/if}</p>
          {if $item.bodytype_name? or $item.condition?}
            {set $str = []}
            {if $item.condition?}{set $str[] = 'состояние: износ ' ~ $item.condition ~ '%'}{/if}
            {if $item.bodytype_name?}{set $str[] = 'кузов: ' ~ $item.bodytype_name}{/if}
            <p class="small">{$str | join : ', '}</p>
          {/if}
          <!-- {$item.id} -->
        </div>
        {*if $showPrice}
          <div>
            <p><span class="items-table-hidden">Цена: </span><span class="price">{$item.price} <i class="fa fa-rub"></i></span></p>
          </div>
          <div>
            <p><span class="items-table-hidden">Средний срок доставки: </span>{$item.delivery}</p>
          </div>
          <div>
            {if $item.reserved?}
              <p>{$_controller->lang['order.order_exist']}</p>
            {else}
              <button data-id="{$item.id}" data-price="{$item.price}" class="btn btn-primary js-button-buy">Заказать</button>
            {/if}
          </div>
        {elseif $_controller->supplier? && $item.sklad_id in list $_controller->supplierSklads}
          <div>
            <p><span class="items-table-hidden">Цена: </span><span class="price">{$item.price} <i class="fa fa-rub"></i></span> (цена поставщика)</p>
          </div>
        {else}
          <div>
            <p><a href="#auth_form" class="js-inlinepopup">Войдите или зарегистрируйтесь</a>, чтобы увидеть цену.</p>
          </div>
        {/if*}
        
        {if $_controller->supplier? && $item.sklad_id in list $_controller->supplierSklads}
          <div>
            <p><span class="items-table-hidden">Цена: </span><span class="price">{$item.price} <i class="fa fa-rub"></i></span> (цена поставщика)</p>
          </div>
        {else}        
          <div>
            <p class="text-nowrap"><span class="items-table-hidden">Цена: </span><span class="price">{$item.price} <i class="fa fa-rub"></i></span>
              {if !$_core->isAuth}
                <a href="help?article=price" class="mp-ajax-popup-align-top link-clear"><i class="fa fa-exclamation-circle"></i></a>
              {/if}
            </p>
          </div>
          <div>
            <p><span class="items-table-hidden">Средний срок доставки: </span>{$item.delivery}</p>
          </div>
          {if $canBuy}
            <div>
              {if $item.reserved?}
                <p>{$_controller->lang['order.order_exist']}</p>
              {else}
                <button data-id="{$item.id}" data-price="{$item.price}" class="btn btn-primary js-button-buy">Заказать</button>
              {/if}
            </div>
          {/if}
        {/if}
        
        
      </li>
    {/foreach}
  </ul>
  
  {if $pagination}
    <nav class="pull-left">
      <ul class="pagination">
        {foreach $pagination as $page => $type}
          {switch $type}
            {case 'first'}
              <li><a href="{$_controller->makePageUrl($page)}">«</a></li>
            {case 'last'}
              <li><a href="{$_controller->makePageUrl($page)}">»</a></li>
            {case 'current'}
              <li class="active"><a href="{$_controller->makePageUrl($page)}">{$page}</a></li>
            {case default}
              <li><a href="{$_controller->makePageUrl($page)}">{$page}</a></li>
          {/switch}
        {/foreach}
      </ul>
    </nav>
  {/if}
  
  {* форма подтверждения заказа *}
  {if $showPrice}{include '_cars.buy.form.tpl'}{/if}
  
  {case 'marks'}
  <ul class="items-list marks">
    {foreach $items as $item}
      <li><a href="{$item.uri}"><i class="sprite-cars-{$item.name|lower|replace:' ':'-'}"></i>&nbsp;<span>{$item.name}</span></a></li>
      {/foreach}
  </ul>
  {case default}
  <ul class="items-list">
    {foreach $items as $item}
      <li><a href="{$item.uri}">{$item.name}</a></li>
      {/foreach}
  </ul>
  {/switch}        

{/if}