$(document).ready(function () {
  
  function itemsManage() {
    // published checkbox
    $('.js-item-published-checkbox').change(function(){
      var $checkbox = $(this);
      var href = 'items/publish?id=' + $checkbox.data('id') + '&publish=' + ($checkbox.prop('checked') ? 1 : 0);
      $.getJSON(href, function(res) {
        if (!res.success) {
          $checkbox.attr('checked', !$checkbox.prop('checked'));
          Brevis.Message.error(res.message);
        } else {
          Brevis.Message.success(res.message);
        }
      });
    });
    // moderate button
    $('.js-item-moderate').click(function(){
      var $button = $(this);
      var $sibling = $button.siblings('button').first(); // соседняя кнопка с противоположным действием
      var href = 'items/moderate?id=' + $button.data('id') + '&moderate=1';
      $.getJSON(href, function(res) {
        if (res.success) {
          $button.prop('disabled', true);
          $sibling.prop('disabled', false);
        }
        $.jGrowl(res.message);
      });
    });
    // unmoderate button
    $('.js-item-unmoderate').click(function (event) {
      var $button = $(this);
      if ($(this).prop('disabled') === false) {
        $.magnificPopup.open({
          items: {
            src: '#moderate_form',
            type: 'inline',
            focus: '#moderate_form_moderate_message'
          },
          callbacks: {
            open: function() {
              $('#moderate_form_id').val($button.data('id'));
              $('#moderate_form_moderate_message').val('');
            }
          }
        });
      }
    });
    // remove button
    $('.js-item-remove').click(function (event) {
      var $button = $(this);
      $.magnificPopup.open({
        items: {
          src: '#remove_form',
          type: 'inline'
        },
        callbacks: {
          open: function() {
            $('#remove_form_id').val($button.data('id'));
          }
        }
      });
    });
  };
    
  itemsManage();
  $(document).on('ajaxlinks_load', function(){
    itemsManage();
  });
  
  // кнопка "Отмена"
  $('#moderate_form a, #remove_form a').click(function(e){ 
    e.preventDefault();
    $.magnificPopup.close();
  });
  
  // форма причины отказа от модерации
  $('#moderate_form').ajaxForm({ 
      dataType: 'json', 
      success: function(res, statusText, xhr, form) {
        form.find('.has-error').removeClass('has-error');
        form.find('.text-danger').empty();
        if (res.success) {
          // меняем состояние кнопок модерации, закрываем popup
          $('button.js-item-unmoderate[data-id="'+$('#moderate_form_id').val()+'"]').prop('disabled', true);
          $('button.js-item-moderate[data-id="'+$('#moderate_form_id').val()+'"]').prop('disabled', false);
          $.magnificPopup.close();
        }
        $.jGrowl(res.message);
      }
  });
  
  // форма удаления
  $('#remove_form').ajaxForm({ 
      dataType: 'json', 
      success: function(res, statusText, xhr, form) {
        if (res.success) {
          // удаляем строку, пересчитываем нумерацию, закрываем popup
          var $button = $('button.js-item-remove[data-id="'+$('#remove_form_id').val()+'"]');
          var $table = $button.parents('table');
          var $tr = $button.parents('tr'); // строка таблицы
          var index = $tr.find('td').first().html(); // порядковый номер строки
          $tr.remove();
          Brevis.recalcTableRows($table, index);
          $.magnificPopup.close();
        }
        $.jGrowl(res.message);
      }
  });
  
  //    $('.js-item-remove').click(function(){
//      var $button = $(this);
//      var $table = $button.parents('table');
//      var $tr = $button.parents('tr'); // строка таблицы
//      var index = $tr.find('td').first().html(); // порядковый номер строки
//      var href = 'items/remove?id=' + $button.data('id');
//      $.getJSON(href, function(res) {
//        if (res.success) {
//          $tr.remove();
//          Brevis.recalcTableRows($table, index);
//        }
//        $.jGrowl(res.message);
//      });
//    });
  
  (function(){
      var $form = $('#item_form');
      if ($form) {
        var href = 'items/get/cars';
        var $mark = $form.find('select[name="mark_key"]');
        var $model = $form.find('select[name="model_key"]');
        var $category = $form.find('select[name="category_key"]');
        var $element = $form.find('select[name="element_key"]');
        var $vendor = $form.find('input[name="vendor_code"]');
        var $name = $form.find('input[name="name"]');
        var $title = $('h1.title');
        var $price = $form.find('input[name="price"]');
        var $avPrice = $('#average_price');
        
        function updateOptions($el, options) {
          $el.empty().html(options).trigger('change');
        }
        
        function genName() {
          var name = $element.find('option:selected').text() + ' ' +
                  $mark.find('option:selected').text() + ' ' +
                  $model.find('option:selected').text();
          var vendor = $vendor.val();
          if (vendor) {
            name = name + ' (' + vendor + ')';
          }
          $name.val(name);
          $title.text(name);
        }
        
        function price() {
          var mark = $mark.val(),
              model = $model.val(),
              category = $category.val(),
              element = $element.val();
          if (category != '' && element != '') {
            $.getJSON('items/get/price?mark='+mark+'&model='+model+'&category='+category+'&element='+element, function(res) {
              if (res.success) {
                $avPrice.text(res.data.result);
                $avPrice.parent().show();
                return;
              }
            });
          }
          $avPrice.parent().hide();
        }
        
        $mark.change(function(){
          $.getJSON(href+'?mark='+this.value, function(res) {
            if (res.success) {
              updateOptions($model, res.data.results);
              genName();
            }
          });
        });
        
        $category.change(function(){
          $.getJSON(href+'?category='+this.value, function(res) {
            if (res.success) {
              updateOptions($element, res.data.results);
              genName();
            }
          });
        });
        
        $model.change(function(){
          genName();
          price();
        });
        $element.change(function(){
          genName();
          price();
        });
        $vendor.change(function(){genName();});
        $vendor.keyup(function(){genName();});
        
        if ($name.val()) {
          $title.text($name.val());
        }
        
        $avPrice.click(function(){
          $price.val($(this).text());
        });
        
        price();
        
      }
    })();
  
  // название детали
  (function(){
  
    var elementSel = '#item_form_element_key',
        categorySel = '#item_form_category_key',
        markSel = '#item_form_mark_key',
        modelSel = '#item_form_model_key',
        vendorSel = '#item_form_vendor_code',
        nameSel = '#item_form_name';
    
    
    
    $([elementSel, categorySel, markSel, modelSel].join(',')).on('change', function(event){
      genName();
    });
    
  });
  
  // images
  (function(){
    
    // checkout remove all button
    function checkoutRemoveAllButton() {
      var $button = $('.js-admin-image-gallery-remove-all');
      $('.admin-image-gallery li').length == 0 ? $button.hide() : $button.show();
    }
    
    // remove button
    function removeButtons(cnt) {
      $(cnt).find('.js-admin-image-gallery-remove').click(function(){
        var $button = $(this);
        var href = 'items/images/removeimage?id=' + $button.data('id') + '&item_id=' + $button.data('item_id');
        $.getJSON(href, function(res) {
          if (res.success) {
            $button.parents('li').remove();
            checkoutRemoveAllButton();
          }
          $.jGrowl(res.message);
        });
      });
    }
    removeButtons('.admin-image-gallery');
    
    // remove all button
    $('.js-admin-image-gallery-remove-all').click(function(){
      var $button = $(this);
      var href = 'items/images/removeimage?item_id=' + $button.data('item_id');
      $.getJSON(href, function(res) {
        if (res.success) {
          $button.hide();
          $('.admin-image-gallery').empty();
        }
        $.jGrowl(res.message);
      });
    });
    
    checkoutRemoveAllButton();
    
    $("#file_upload").fileinput({
      language: 'ru',
      theme: 'fa',
      browseOnZoneClick: true,
      allowedFileExtensions: ['jpg', 'jpeg'],
//      allowedFileTypes: ['image'],
      allowedPreviewTypes: ['image'],
      uploadUrl: "items/images/uploadone",
      uploadExtraData: {
        item_id: $('#form_images_add_item_id').val()
      },
      uploadAsync: true,
      maxFileCount: 10,
      maxFileSize: 10000,
      resizeImage: false, // если использовать ресайз, приходит другой хеш файла
//      maxImageWidth: 1124,
//      maxImageHeight: 1124,
//      resizePreference: 'width',
//      resizeImageQuality: 1.00,
      fileActionSettings: {
        showZoom: false
      },
      previewSettings: {
        image: {width: "150px", height: "auto"}
      }
    }).on('fileuploaded', function(event, data, previewId, index) {
        $('.admin-image-gallery').append('<li id="added_'+previewId+'"><a href="assets/images/data/'+data.response.prefix+'/'+data.response.filename+'"><img src="assets/images/data/'+data.response.prefix+'/120x90/'+data.response.filename+'"></a><button title="Удалить" class="js-admin-image-gallery-remove" data-id="'+data.response.id+'" data-item_id="'+data.response.item_id+'"><i class="fa fa-trash-o"></i></button><i class="fa fa-arrows"></i></li>');
        removeButtons('#added_'+previewId);
        checkoutRemoveAllButton();
    }).on('fileunlock', function() {
        // так и непонял как скрыть правильно...
        $('.file-preview-success').remove();
//        console.log($(this).fileinput('getFileStack').length);
        if ($(this).fileinput('getFileStack').length == 0) {
          $(this).fileinput('clear');
        }
        
    }).on('fileuploaderror', function(event, data, msg) {
        $.jGrowl(msg);
    });
    
    // sortable admin gallery
    $(".admin-image-gallery").sortable({
      handle: 'i.fa-arrows',
      vertical: false,
      onDragStart: function ($item, container, _super) {
        var offset = $item.offset(),
            pointer = container.rootGroup.pointer;
        adjustment = {
          left: pointer.left - offset.left,
          top: pointer.top - offset.top
        };
        _super($item, container);
      },
      onDrag: function ($item, position) {
        $item.css({
          left: position.left - adjustment.left,
          top: position.top - adjustment.top
        });
      },
      onDrop: function ($item, container, _super) {
        var $items = $(".admin-image-gallery").find('.js-admin-image-gallery-remove');
        var item_id = $items.data('item_id');
        var order = [];
        $items.each(function(){
          order.push($(this).data('id'));
        });
        var href = 'items/images/sort?item_id=' + item_id + '&order=' + order;
        $.getJSON(href, function(res) {
          if (res.message) {
            $.jGrowl(res.message);
          }
        });
        
        _super($item, container);
      }
    });
    
  })();
  
  // форма запроса на добавление данных в каталог
  $('.js-catalog-request [data-show]').click(function(e){
    var show = $(this).data('show');
    $(this).parents('form').find('input[name="'+show+'"]').show().focus();
  });
});