{* Базовый шаблон письма *}
{block 'greeting'}Здравствуйте{if $name?}, {$name}{/if}!{/block}

{block 'content'}
{$content}
{/block}

{$_core->siteUrl} 
Вход в личный кабинет: {$_core->siteUrl}/user
{$.php.date('d-m-Y H:i', time())}