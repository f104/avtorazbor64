{extends $_controller->isAjax ? '_ajax.tpl' : '_base.tpl'}

{block 'content'}
<div class="row">
  {if !$popup}
  <div class="col-sm-4">
    {$formEdit}
    {$formDelete}
  </div>
  {/if}
  <div class="col-sm-{$popup ? 12 : 8}">
    <p>
      {if $user.createdon?}
        Зарегистрирован: {$user.createdon|date:"d-m-Y H:i"}
      {/if}
      {if $user.lastlogin?}
        <br>Последний вход: {$user.lastlogin|date:"d-m-Y H:i"}
      {/if}
    </p>
    
    {if $popup}
      <p>Email: <a href="mailto:{$user.email}">{$user.email}</a><br>
        Телефон: {$user.phone}<br>
        Регион: {$region}<br>
        Город: {$city}
      </p>
    {/if}
    
    {switch $userGroup.id}
      {case 1}
      {* покупатели *}
        {* баланс *}
        <h3>Баланс покупателя: <span class="js-userbalance_{$user.id}">{$user.balance}</span>&nbsp;<i class="fa fa-rub"></i></span> 
          {if !$popup}&nbsp;&nbsp;&nbsp;<a href="fees/add?user_id={$user.id}" class="btn btn-primary btn-xs js-ajaxpopup">Пополнить</a>{/if}
        </h3>
          {$user.fees}
        {* заказы *}
        {if $user.orders}
          <h3>Заказы покупателя</h3>
          {$user.orders}
        {/if}
    {/switch}
    
  </div>
</div>
{/block}