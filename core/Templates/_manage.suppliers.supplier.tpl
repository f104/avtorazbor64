{* Шаблон просмотра/рекдатирования поставщика *}

<div class="row">
  <div class="col-sm-6">
    {$form}
  </div>
  <div class="col-sm-6">
      {if $supplier.id?}
        {if $supplierInfo?}
          <a href="/suppliers/info?id={$supplier.id}" class="btn btn-primary">Анкета поставщика</a>
        {else}
          <p class="text-danger">Анкета не заполнена.</p><a href="/suppliers/info?id={$supplier.id}" class="btn btn-primary">Заполнить</a>
        {/if}
      {/if}
    </dl>
  </div>
</div>