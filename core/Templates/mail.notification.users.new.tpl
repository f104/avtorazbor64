{* Уведомление о новом пользователе *}
{extends '_mail.tpl'}

{block 'content'}
Вы просили уведомить о регистрации нового пользователя.

{foreach $fields as $k=>$v}
  {$lang[$prefix~$k]}: {$v}
  
{/foreach}
{/block}