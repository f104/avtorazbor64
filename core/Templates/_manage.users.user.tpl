{* Шаблон просмотра/рекдатирования пользователя *}

<div class="row">
  <div class="col-sm-6">
    {$form}
  </div>
  <div class="col-sm-6">
      {if $user.id?}
        <dl class="dl-h1orizontal">
          <dt>Зарегистрирован:</dt><dl>{$user.createdon|date:"d-m-Y H:i"}</dl>
        {if $user.lastlogin?}
          <dt>Последний вход:</dt><dl>{$user.lastlogin|date:"d-m-Y H:i"}</dl>
        {/if}
      {/if}
    </dl>
  </div>
</div>