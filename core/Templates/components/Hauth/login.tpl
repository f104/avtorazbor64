<p>Вы можете войти, используя аккаунты в социальных сетях.</p>
<ul class="ha-auth" data-remember="0" data-return="">
  {foreach $providers as $provider}
    <li><a class="ha ha-{$provider}" href="{$url}?provider={$provider}" title="Войти с помощью {$provider}">{$provider}</a></li>
  {/foreach}
</ul>