<table class="table table-condensed table-bordered">
  <tbody>
    {foreach $rows as $k => $v}
      <tr>
        <td>{$lang[$prefix~$k]}</td>
        <td>{$v}</td>
      </tr>
    {/foreach}
  </tbody>
</table>