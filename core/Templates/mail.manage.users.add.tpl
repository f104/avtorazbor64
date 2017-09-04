{* Уведомление пользователя о том, что для него заведен аккаунт *}
{extends '_mail.tpl'}

{block 'content'}
Для вас был заведен аккаунт на сервере {$_core->siteDomain}.

Данные для входа
логин (email): {$email} 
пароль: {$password} 
{/block}