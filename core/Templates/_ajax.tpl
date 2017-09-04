{* шаблон для ajax popup *}
<article>
  <h1 class="title">
    {block 'h1'}{$title}{/block}
  </h1>
  {if $message}
    <p class="alert alert-{$success == true ? 'success' : 'danger'}">{$message}</p>
  {/if}
  {block 'content'}
    {$content}
  {/block}            
</article>