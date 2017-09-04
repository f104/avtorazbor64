{if $selects}
  <form id="cars_nav_form">
    <a href="{$home}">Все производители</a>
    {foreach $selects as $name => $options}
      <select name="{$name}">
        <option value="">показать все</option>
        {foreach $options as $value => $option}
          <option value="{$value}" {$option.selected}>{$option.name}</option>
        {/foreach}
      </select>
    {/foreach}
    <button type="submit" class="js-hidden">&crarr;</button>
  </form>
{/if}