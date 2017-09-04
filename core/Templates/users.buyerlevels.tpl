{extends '_base.tpl'}

{block 'content'}

  {foreach $items as $item}
    <form id="buyers_level_{$item_id}" class="form-inline js-ajaxform" method="post" action="users/buyerlevels/update">
      <div class="form-group">
        <input type="text" class="form-control" name="name" maxlength="50" value="{$item['name'] | e}" required>
        <div class="text-danger"></div>
      </div>
      <div class="form-group">
        <input type="number" class="form-control" name="increase" min="0" value="{$item['increase']}" required>
        <div class="text-danger"></div>
      </div>
      <input type="hidden" name="id" value="{$item['id']}">
      <button type="submit" name="update" class="btn btn-primary"><i class="fa fa-check"></i></button>
      <button type="reset" class="btn btn-default"><i class="fa fa-undo"></i></button>
      {if $item['allow_remove']?}
        <button type="submit" name="remove" class="btn btn-danger"><i class="fa fa-times"></i></button>
      {/if}
      <div class="remove-wrapper hidden"><label><input type="checkbox" name="remove" value="1"> {$_controller->lang['remove']}</label></div>
    </form>
  {/foreach}
  
  <form class="form-inline js-ajaxform" method="post" action="users/buyerlevels/update">
    <div class="form-group">
      <input type="text" class="form-control" name="name" maxlength="50" value="{$newItem['name'] | e}" required
             placeholder="{$_controller->lang['user.buyerlevels_new'] | e}">
      <div class="text-danger"></div>
    </div>
    <div class="form-group">
      <input type="number" class="form-control" name="increase" min="0" value="{$newItem['increase']}" required>
      <div class="text-danger"></div>
    </div>
    <input type="hidden" name="id" value="0">
    <button type="submit" name="update" class="btn btn-primary"><i class="fa fa-check"></i></button>
    <button type="reset" class="btn btn-default"><i class="fa fa-undo"></i></button>
  </form>
    
{/block}
{block 'js'}
  {parent}
  <script>
    $(document).ready(function () {
      $(document).on('af_complete', function (event, form, res) {
        if (res.data.removed) {
          form.remove();
        }
      });
    });
  </script>
{/block}