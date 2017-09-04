{extends '_base.tpl'}

{block 'content'}  
<div class="row">
  <div class="col-sm-6">
    {if $statusMessage}
      <p class="text-warning">{$statusMessage}</p>
    {/if} 
    {$formEdit}
    {$formDelete}
    {$formRequestCar}
    {$formRequestCategory}
  </div>
  <div class="col-sm-6">
    {if !$item_id}
      <p>{$_controller->lang['upload_later_desc']}</p>
    {else}
      {if $imagesBinary != 0}
        <p class="text-warning">{$imagesBinary | decl : 'фотография загружена как бинарный файл и в настоящий момент обрабатывается,фотографии загружены как бинарные файлы и в настоящий момент обрабатываются,фотографий загружены как бинарные файлы и в настоящий момент обрабатываются' : true}.</p>
      {/if}
      <section>
        <ul class="admin-image-gallery js-popup-gallery">
          {foreach $images as $image}
            <li>
              {if $image->filename?}
                <a href="assets/images/data/{$image->prefix}/{$image->filename}"><img src="assets/images/data/{$image->prefix}/120x90/{$image->filename}"></a>
              {else}
                {*remote file*}
                <a href="{$image->url}"><img src="{$image->url}"></a>
              {/if}
              <button title="Удалить" class="js-admin-image-gallery-remove" data-id="{$image->id}" data-item_id="{$image->item_id}"><i class="fa fa-trash-o"></i></button>
              <i class="fa fa-arrows" title="Сортировка"></i>
            </li>
          {/foreach}
        </ul>
          <button class="btn btn-danger js-admin-image-gallery-remove-all" data-item_id="{$item_id}"><i class="fa fa-trash-o"></i></span> Удалить все</button>
      </section>
      {$formImages}
    {/if}
  </div>
</div>
{/block}