{* Уведомление администратору о запросе на добавление марки/модели или категории/элемента *}
{extends '_mail.tpl'}

{block 'content'}
Пользователь {$user.name} ({$user.email}) предложил добавить в каталог запись:

{if $fields.mark?}{$_controller->lang.mark}: {$fields.mark}{/if}

{if $fields.model?}{$_controller->lang.model}: {$fields.model}{/if}

{if $fields.category?}{$_controller->lang.category}: {$fields.category}{/if}

{if $fields.element?}{$_controller->lang.element}: {$fields.element}{/if}
{/block}