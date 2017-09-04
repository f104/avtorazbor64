<br>
<fieldset>
  <legend>Привязанные аккаунты</legend>
  <ul class="ha-profile">
    {foreach $providers as $providerKey => $provider}
      {if $provider.active?}
      <li><a class="ha ha-{$providerKey} active" title="Отключить связь" href="{$url}?provider={$providerKey}&action=unbind">{$provider.name}</a></li>
      {else}
        <li><a class="ha ha-{$providerKey}" href="{$url}?provider={$providerKey}&return={$return}">{$provider.name}</a></li>
      {/if}
    {/foreach}
  </ul>
</fieldset>