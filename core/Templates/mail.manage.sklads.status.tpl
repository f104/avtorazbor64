{* Уведомление поставщика о смене статуса склада*}
{extends '_mail.tpl'}

{block 'content'}
Статус Вашего склада "{$sklad_name}" был изменен на "{$status}".
{/block}