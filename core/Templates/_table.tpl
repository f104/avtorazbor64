{if !$_controller->isAjax}
  <div class="row">
    <div class="col-sm-9 js-ajaxwrapper" id="ajaxwrapper">
{/if}
{var $sortUrl = $_controller->makeSortUrl()}
  <table class="table table-condensed table-striped">
    <thead>
      <th>&nbsp;</th>
      {foreach $cols as $key => $col}
        <th class="{$col.class} {if $_controller->sortby == $key}text-warning{/if} text-nowrap">
          {$col.title}
          {if !isset($col.sortable) or $col.sortable}
            <a class="link-clear js-ajaxlink" href="{$sortUrl}&sortby={$key}"><i class="fa fa-sort"></i></a>
          {/if}
        </th>
      {/foreach}    
    </thead>
    {if $rows}
      <tfoot>
        <tr>
          <td colspan="{$cols|count + 1}" class="text-muted">
            Всего: <span class="js-table-row-count">{$total ?: $rows|count}</span>
            {if $totalSum}
              на сумму {$totalSum|decl:'рубль,рубля,рублей':true}
            {/if}
          </td>
        </tr>
     </tfoot>
    {/if}
    <tbody>
      {if $rows}
        {foreach $rows as $row}
          <tr>
            <td class="text-muted js-table-row-index">{$offset + $row@index + 1}</td>
            {foreach $cols as $key=>$col}
              <td class="{$col.class}">
                {if $col.tpl?}
                  {include "inline:{$col['tpl']}"}
                {else}
                  {$row.$key}
                {/if}
              </td>
            {/foreach}
          </tr>
        {/foreach}
      {else}
        <tr><td colspan="{$cols|count + 1}" class="text-center">Нет данных для вывода</td></tr>
      {/if}
    </tbody>
  </table>
  <div class="clearfix">
    {if $rows and $pagination}
      <nav class="pull-left">
        <ul class="pagination">
          {foreach $pagination as $page => $type}
            {switch $type}
              {case 'first'}
                <li><a class="js-ajaxlink" href="{$_controller->makePageUrl($page)}">«</a></li>
              {case 'last'}
                <li><a class="js-ajaxlink" href="{$_controller->makePageUrl($page)}">»</a></li>
              {case 'current'}
                <li class="active"><a class="js-ajaxlink" href="{$_controller->makePageUrl($page)}">{$page}</a></li>
              {case default}
                <li><a class="js-ajaxlink" href="{$_controller->makePageUrl($page)}">{$page}</a></li>
            {/switch}
          {/foreach}
        </ul>
      </nav>
    {/if}
    {if $_controller->exportColumns?}
      <a href="#export_form" class="pull-right btn btn-sm btn-default js-inlinepopup"><i class="fa fa-file-excel-o"></i> Экспорт в xls</a>
      <form id="export_form" method="post" action="export" class="white-popup-block mfp-hide">
        {if $total > 5000}
          <p>Слишком большой объем данных. Уточните параметры запроса, используя фильтры.</p>
        {else}
          <fieldset>
            <legend>Экспорт данных</legend>
            <input type="hidden" name="classname" value="{$_controller->getControllerName()}">
            <input type="hidden" name="filters" value="{$_controller->exportFilters|json_encode|e}">
            <div class="form-group" style="padding-left:20px">
              {foreach $_controller->exportColumns as $column}
                {set $ef_langPrefix = $_controller->getDataClassName(true, true)~'.'}
                <label class="checkbox">
                  <input type="checkbox" name="columns[]" value="{$column|e}" checked> {$_controller->lang[$ef_langPrefix~$column]}
                </label>
              {/foreach}
            </div>
          </fieldset>
          <button type="submit" class="btn btn-primary">Скачать</button>
        {/if}
        <button type="reset" class="btn btn-default">Отмена</button>
      </form>      
    {/if}
  </div>
{if !$_controller->isAjax}
    </div>
    <div class="col-sm-3 _sticky datatable-header">
      {if $addPermission? and $_controller->checkPermissions($addPermission)}
        <div class="datatable-add"><a href="{$addUrl}" class="btn btn-primary"><i class="fa fa-plus"></i> Добавить</a></div>
      {/if}    
      <div class="datatable-filters">{$filters}</div>
    </div>
  </div>
{/if}