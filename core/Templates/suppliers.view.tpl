{extends '_base.tpl'}

{block 'content'}
  <section class="row">
    <div class="col-sm-4">
      {$form}
    </div>
    <div class="col-sm-8">
      <p>Количество складов: {if $supplier.skladCount?}<a href="sklads?supplier_id={$supplier.id}">{$supplier.skladCount}</a>{else}{$supplier.skladCount}{/if}</p>
      {* заказы *}
      {if $supplier.orders}
        <h3>Заказы поставщика</h3>
        {$supplier.orders}
      {/if}
      {* платежи *}
      {if $supplier.payments}
        <h3>Платежи поставщика</h3>
        {$supplier.payments}
      {/if}
      {$supplier.info}
    </div>
  </section>
{/block}