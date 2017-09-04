$(document).ready(function () {
  
  function manage() {
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
    // status button
    $('.js-order-status').click(function (event) {
      var $button = $(this);
//      var $sibling = $button.siblings('button.js-order-status').first(); // соседняя кнопка с противоположным действием
      var href = 'orders/status?id=' + $button.data('id') + '&status=' + $button.data('status');
      $.getJSON(href, function(res) {
        if (res.success) {
          $button.parents('td').empty();
//          $sibling.prop('disabled', false);
          if (res.data.new_status_name) {
            $('.js-order-label-'+$button.data('id')).text(res.data.new_status_name);
          }
          if (res.data.remove_payment_form) {
            $('#payment_'+$button.data('id')).remove();
          }
        }
        $.jGrowl(res.message);
      });
    });
    // withdraw button
    $('.js-order-withdraw').click(function (event) {
      var $button = $(this);
      var href = 'orders/withdraw?id=' + $button.data('id');
      $.getJSON(href, function(res) {
        if (res.success) {
          if (res.data.new_status_name) {
            $('.js-order-label-'+$button.data('id')).text(res.data.new_status_name);
          }
          $('.js-order-status[data-id='+$button.data('id')+']').remove();
          $button.remove();
        }
        $.jGrowl(res.message);
      });
    });
    // status form
    $('.status-form').each(function(i){
      var $_this = $(this);
      $_this.find('select').change(function(e){
        var val = $(this).val();
        if (val == 0) return;
        var href = 'orders/status?id=' + $_this.data('id') + '&status=' + val;
        $.getJSON(href, function(res) {
          if (res.success) {
            if (res.data.new_status_name) {
              $('.js-order-label-'+$_this.data('id')).text(res.data.new_status_name);
            }
          }
          $.jGrowl(res.message);
        });
      });
    });
  };
    
  manage();
  $(document).on('ajaxlinks_load', function(){
    manage();
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
  
  // кнопка "Отмена"
  $('#remove_form a').click(function(e){ 
    e.preventDefault();
    $.magnificPopup.close();
  });
  
  // Обработка селектов в форме замены
  (function(){
      var $form = $('#order_form');
      if ($form.length != 0) {
        var href = 'orders/itemview/get';
        var $id = $form.find('input[name="id"]');
        var $mark = $form.find('select[name="mark_key"]');
        var $model = $form.find('select[name="model_key"]');
        var $category = $form.find('select[name="category_key"]');
        var $element = $form.find('select[name="element_key"]');
        var $itemsWrapper = $('.js-items-wrapper');
        
        var query = {
          id: $id.val(), 
          mark_key: $mark.val(), 
          model_key: getMarkKey($model.val()),
          year_key: getYearKey($model.val()),
          category_key: $category.val(),
          element_key: $element.val()
        }
        
        function updateOptions($el, options) {
          $el.empty().html(options);
          $itemsWrapper.empty();
        }
        
        function getMarkKey(value) {
          return value.substring(0,3);
        }
        
        function getYearKey(value) {
          return value.substring(3);
        }
        
        $mark.change(function(){
          query.mark_key = this.value;
          query.model_key = null;
          query.year_key = null;
          query.category_key = null;
          query.element_key = null;
          $.getJSON(href+'?'+$.param(query), function(res) {
            if (res.success) {
              updateOptions($model, res.data.results);
              $category.empty().attr('disabled', true);
              $element.empty().attr('disabled', true);
            }
          });
        });
        
        $model.change(function(){
          query.model_key = getMarkKey(this.value);
          query.year_key = getYearKey(this.value);
          query.category_key = null;
          query.element_key = null;
          if (this.value) {
            $.getJSON(href+'?'+$.param(query), function(res) {
              if (res.success) {
                updateOptions($category, res.data.results);
                $category.attr('disabled', false);
              }
            });
          } else {
            $category.empty().attr('disabled', true);
          }
          $element.empty().attr('disabled', true);
        });
        
        $category.change(function(){
          query.category_key = this.value;
          query.element_key = null;
          if (this.value) {
            $.getJSON(href+'?'+$.param(query), function(res) {
              if (res.success) {
                updateOptions($element, res.data.results);
                $element.attr('disabled', false);
              }
            });
          } else {
            $element.empty().attr('disabled', true);
          }
        });
        
        $element.change(function(){
          query.element_key = this.value;
          $itemsWrapper.empty();
          if (this.value) {
            $.getJSON(href+'?'+$.param(query), function(res) {
              if (res.success) {
                $itemsWrapper.html(res.data.results);
                Brevis.popupGallery('.js-popup-gallery');
                Brevis.ajaxForm('.js-ajaxform');
              }
            });
          }
        });
        
        
      }
    })();
    
});