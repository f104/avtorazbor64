{extends '_base.tpl'}

{block 'content'}
  
  <div class="js-tab-container">
    
    <ul class="nav nav-tabs">
      <li><a href="#cars">{$_controller->lang['catalog.cars_tab']}</a></li>
      <li><a href="#categories">{$_controller->lang['catalog.categories_tab']}</a></li>
      <li><a href="#increases">{$_controller->lang['catalog.increase_tab']}</a></li>
      <li><a href="#bodytype">{$_controller->lang['catalog.bodytype_tab']}</a></li>
    </ul>
            
    <div id="cars">
      
      <div class="catalog-wrapper js-catalog-wrapper">
        <div class="js-catalog-list js-catalog-marks">
          <h3>{$_controller->lang['catalog.mark']}</h3>
          <ul data-type="mark" data-update="model">
          {foreach $mark as $item}
            <li {if $.get['mark_key']? and $.get['mark_key'] == $item.mark_key}class="selected"{/if}>
              <a href="catalog?mark_key={$item.mark_key}" 
                  data-key="{$item.mark_key}"
                  data-alias="{$item.alias}"
                 >{$item.mark_name}</a> 
              <span class="badge">{$item.mark_key}</span>
            </li>
          {/foreach}
          </ul>
        </div>
        <div class="js-catalog-list js-catalog-models">
          <h3>{$_controller->lang['catalog.model']}</h3>
          <ul data-type="model" data-update="year">
          {foreach $model as $item}
            <li {if $.get['model_key']? and $.get['model_key'] == $item.model_key}class="selected"{/if}>
              <a href="catalog?mark_key={$item.mark_key}&model_key={$item.model_key}" data-key="{$item.model_key}">{$item.model_name}</a>
              <span class="badge">{$item.model_key}</span>
            </li>
          {/foreach}
          </ul>
        </div>
        <div class="js-catalog-list js-catalog-years">
          <h3>{$_controller->lang['catalog.year']}</h3>
          <ul data-type="year">
          {foreach $year as $item}
            <li>
              <a href="#" data-key="{$item.year_key}" class="catalog-disabled-link" data-year_start="{$item.year_start}" data-year_finish="{$item.year_finish}">{$item.year_name}</a>
              <span class="badge">{$item.year_key}</span>
            </li>
          {/foreach}
          </ul>
        </div>
      </div>
        
    </div>
        
    <div id="categories">
      
      <div class="catalog-wrapper js-catalog-wrapper">
        <div class="js-catalog-list ce js-catalog-categories">
          <h3>{$_controller->lang['catalog.categories']}</h3>
          <ul data-type="category" data-update="element">
          {foreach $category as $item}
            <li {if $.get['category_key']? and $.get['category_key'] == $item.key}class="selected"{/if}>
              <a href="catalog?category_key={$item.key}" data-key="{$item.key}">{$item.name}</a> 
              <span class="badge">{$item.key}</span>
            </li>
          {/foreach}
          </ul>
        </div>
        <div class="js-catalog-list ce js-catalog-elements">
          <h3>{$_controller->lang['catalog.elements']}</h3>
          <ul data-type="element">
          {foreach $element as $item}
            <li {if $.get['element_key']? and $.get['element_key'] == $item.key}class="selected"{/if}>
              <a href="#" data-key="{$item.key}" data-increase="{$item.increase_category_id}" class="catalog-disabled-link">{$item.name}</a>
              <span class="badge">{$item.key}</span>
            </li>
          {/foreach}
          </ul>
        </div>
      </div>
      
    </div>
            
    <div id="increases">
      <div class="catalog-wrapper js-catalog-wrapper">
        <div class="js-catalog-list ce js-catalog-increases">
          <ul data-type="increase">
          {foreach $increases as $item}
            <li>
              <a href="#" data-key="{$item.id}" class="catalog-disabled-link">{$item.increase}</a> 
              <span class="badge">{$item.id}</span>
            </li>
          {/foreach}
          </ul>
        </div>
      </div>
    </div>
    
    <div id="bodytype">
      <div class="catalog-wrapper js-catalog-wrapper">
        <div class="js-catalog-list ce js-catalog-bodytypes">
          <ul data-type="bodytype">
          {foreach $bodytypes as $item}
            <li>
              <a href="#" data-key="{$item.id}" class="catalog-disabled-link">{$item.name}</a> 
              <span class="badge">{$item.id}</span>
            </li>
          {/foreach}
          </ul>
        </div>
      </div>
    </div>
  </div>
{/block}