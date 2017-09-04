<section>
  {if $moderate.items or $moderate.suppliers or $moderate.sklads}
    <h5>Ожидают модерации</h5>
    <ul>
      {if $moderate.items}
        <li><a href="{$_controller->makeUrl('items', ['moderate' => 0])}">{$moderate.items | decl : 'товар,товарa,товаров' : true}</a></li>
      {/if}
      {if $moderate.suppliers}
        <li><a href="{$_controller->makeUrl('suppliers', ['status_id' => 2])}">{$moderate.suppliers | decl : 'поставщик,поставщика,поставщиков' : true}</a></li>
      {/if}
      {if $moderate.sklads}
        <li><a href="{$_controller->makeUrl('sklads', ['status_id' => 2])}">{$moderate.sklads | decl : 'склад,склада,складов' : true}</a></li>
      {/if}
    </ul>
  {/if}
</section>