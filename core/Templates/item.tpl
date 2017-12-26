{* просмотр конкретной детали *}
{extends '_base.tpl'}

{block 'content'}
  <div class="row">
    <div class="col-sm-6">
      <p>Код: {$item.code}
        {if $item.remote_id?}
          <br>Артикул: {$item.remote_id}
        {/if}
      </p>
      {if $item.body_type}
        <p>Кузов: {$item.bodytype_name}</p>
      {/if}
      {if $item.condition}
        <p>
          Состояние: {$item.condition_name}, износ {$item.condition}%
          {if $item.condition_comment}({$item.condition_comment}){/if}
          <p class="text-danger">Внимание! Деталь с&nbsp;дефектом и&nbsp;продаётся со&nbsp;скидкой. Дополнительные фото и&nbsp;информация по&nbsp;запросу.</p>
        </p>
      {/if}
      {if $item.comment?}<p>{$item.comment | nl2br}</p>{/if}
      <p>В наличии на складе</p>

      {*if $showPrice}
      <h4>Цена {$item.price} <i class="fa fa-rub"></i></h4>
      <p>Средний срок доставки: {$item.delivery}</p>
      
      {if $item.reserved?}
      <p>{$_controller->lang['order.order_exist']}</p>
      {else}
      <button data-id="{$item.id}" data-price="{$item.price}" class="btn btn-primary js-button-buy">Заказать</button>
      {/if}
      {elseif $_controller->supplier? && $item.sklad_id in list $_controller->supplierSklads}
      <h4>Цена поставщика {$item.price} <i class="fa fa-rub"></i></h4>
      {else}
      <p><a href="#auth_form" class="js-inlinepopup">Войдите или зарегистрируйтесь</a>, чтобы увидеть цену.</p>
      {/if*}

      {if $_controller->supplier? && $item.sklad_id in list $_controller->supplierSklads}
        <h4>Цена поставщика {$item.price} <i class="fa fa-rub"></i></h4>
        {else}
        <h4>Цена {$item.price} <i class="fa fa-rub"></i></h4>
        <p>Цена указана самовывозом с нашего склада или с доставкой до транспортной компании,  цену и срок доставки в ваш регион уточняйте в транспортной компании.</p>

        {if $canBuy}
          {if $item.reserved?}
            <p>{$_controller->lang['order.order_exist']}</p>
          {else}
            <button data-id="{$item.id}" data-price="{$item.price}" class="btn btn-primary js-button-buy">Заказать</button>
          {/if}
        {elseif !$_core->isAuth}
          <p><a href="#auth_form" class="js-inlinepopup">Войдите или зарегистрируйтесь</a>, чтобы иметь возможность заказать деталь.</p>
        {/if}

      {/if}


    </div>
    <div class="col-sm-6">
      {if $images}
        <div class="popup-gallery-item js-popup-gallery">
          {foreach $images as $image}
            {if $image->filename?}
              <a href="assets/images/data/{$image->prefix}/{$image->filename}" title="{$item.name|e} ({$image->item_key}/{$image->prefix})"><img src="assets/images/data/{$image->prefix}/120x90/{$image->filename}" alt="{$item.name|e}"></a>
              {else}
                {*remote file*}
              <a href="{$image->url}" title="{$item.name|e} ({$image->item_key}/{$image->prefix})"><img src="{$image->url}" alt="{$item.name|e}"></a>
              {/if}
            {/foreach}
        </div>
      {else}
        <div class="item-nophoto"><img src="assets/images/nophoto.png">{if $item.source == '1C'}<button class="btn btn-sm btn-primary js-button-requestphoto">Запросить фото</button>{/if}</div>
        {/if}
    </div>
  </div>
  {* форма подтверждения заказа *}
{if $showPrice}{include '_cars.buy.form.tpl'}{/if}
{* форма запроса фото *}
{if $item.source == '1C' and count($images) == 0}{include '_cars.requestPhoto.form.tpl'}{/if}
{/block}