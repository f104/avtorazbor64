{extends '_base.tpl'}

{block 'desc'}Вы можете отправить нам письмо с помощью формы обратной связи.{/block}

{block 'h1'}{/block}

{block 'feedback btn'}{/block}

{block 'content'}
  {if !$success}
    <div class="row">
      <div class="col-md-6">
        {include '_feedback.form.tpl'}
      </div>
    </div>
  {else}
    <p class="text-success">Ваше сообщение отправлено.</p>
  {/if}
{/block}
{block 'feedback'}{/block}