{extends '_base.tpl'}

{block 'content'}
  
  <div class="js-tab-container">
    
    <div class="checkbox">
      <label><input name="only_empty" type="checkbox" {if $only_empty}checked{/if}>Только пустые</label>
    </div>
    
    <ul class="nav nav-tabs">
      <li><a href="#cars">{$_controller->lang['catalog.cars_tab']}</a></li>
      <li><a href="#categories">{$_controller->lang['catalog.categories_tab']}</a></li>
    </ul>
            
    <div id="cars">
      <br>
      {$cars}
    </div>
    <div id="categories">
      <br>
      {$ce}
    </div>
            
  </div>
{/block}