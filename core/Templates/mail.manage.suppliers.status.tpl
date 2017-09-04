{* Уведомление поставщика о смене статуса *}
{extends '_mail.tpl'}

{block 'content'}
Ваш статус поставщика на сервере {$_core->siteDomain} был изменен на "{$status}".
{/block}