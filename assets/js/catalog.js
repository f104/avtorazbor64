$(document).ready(function () {

  // списки
  var list = {
    mark: $('.js-catalog-marks ul'),
    model: $('.js-catalog-models ul'),
    year: $('.js-catalog-years ul'),
    category: $('.js-catalog-categories ul'),
    element: $('.js-catalog-elements ul'),
    increase: $('.js-catalog-increases ul'),
    bodytype: $('.js-catalog-bodytypes ul')
  }
  // формы
  var $marksForm = $('.js-catalog-marks form');
  var $modelsForm = $('.js-catalog-models form');
  var $yearsForm = $('.js-catalog-years form');
  var $categoriesForm = $('.js-catalog-categories form');
  var $elementsForm = $('.js-catalog-elements form');
  var $increaseForm = $('.js-catalog-increases form');
  // шаблоны
  var tpl = {
    mark: Hogan.compile('<li>\n\
                          <a href="catalog?mark_key={{mark_key}}" data-key="{{mark_key}}" data-alias="{{alias}}">{{mark_name}}</a>\n\
                          <span class="badge">{{mark_key}}</span>\n\
                        </li>'),
    model: Hogan.compile('<li>\n\
                            <a href="catalog?mark_key={{mark_key}}&model_key={{model_key}}" data-key="{{model_key}}">{{model_name}}</a>\n\
                            <span class="badge">{{model_key}}</span>\n\
                          </li>'),
    year: Hogan.compile('<li>\n\
                            <a href="#" data-key="{{year_key}}" class="catalog-disabled-link" data-year_start="{{year_start}}" data-year_finish="{{year_finish}}">{{year_name}}</a>\n\
                            <span class="badge">{{year_key}}</span>\n\
                          </li>'),
    category: Hogan.compile('<li>\n\
                            <a href="catalog?category_key={{key}}" data-key="{{key}}">{{name}}</a>\n\
                            <span class="badge">{{key}}</span>\n\
                          </li>'),
    element: Hogan.compile('<li>\n\
                            <a href="#" data-key="{{key}}" data-increase="{{increase_category_id}}" class="catalog-disabled-link">{{name}}</a>\n\
                            <span class="badge">{{key}}</span>\n\
                          </li>'),
    increase: Hogan.compile('<li>\n\
                            <a href="#" data-key="{{id}}" class="catalog-disabled-link">{{increase}}</a>\n\
                            <span class="badge">{{id}}</span>\n\
                          </li>'),
    bodytype: Hogan.compile('<li>\n\
                            <a href="#" data-key="{{id}}" class="catalog-disabled-link">{{name}}</a>\n\
                            <span class="badge">{{id}}</span>\n\
                          </li>')
  };

  // инициализация списка
  function initList($object) {
    var type = $($object).data('type');
    $object.find('a').click(function(e){
      var _this = this;
      var update = $($object).data('update'); // какой список апдейтить после загрузки данных
      e.preventDefault();
      $object.find('.selected').removeClass('selected');
      resetList(type);
      // get data
      $.getJSON($(this).attr('href'), function(res) {
        if (res.success) {
          list[update].html(templateItems(res.data[update], tpl[update]));
          switch (type) {
            case 'mark':
              initList(list.model);
              $modelsForm.removeClass('hidden').find('input[name=mark_key]').val($(_this).data('key'));
              $yearsForm.find('input[name=mark_key]').val($(_this).data('key'));
              break;
            case 'model':
              initList(list.year);
              $yearsForm.removeClass('hidden').find('input[name=model_key]').val($(_this).data('key'));
              break;
            case 'category':
              initList(list.element);
              $elementsForm.removeClass('hidden').find('input[name=category_key]').val($(_this).data('key'));
              break;
          }
        } else {
          Brevis.Message.error(res.message);
        }
        $('.catalog-wrapper > div').trigger("sticky_kit:recalc");
        list[update].unblock();
      });
      $(this).parent('li').addClass('selected');
    });
    // псевдокнопки редактирования
    $object.find('.js-catalog-update').click(function(e){
      $object.find('li').removeClass('active');
      var $a = $(this).siblings('a');
      var $form = $(this).parents('div.js-catalog-list').find('form');
      resetForm($form);
      $form.appendTo($(this).parents('li'));
      $(this).parents('li').addClass('active');
      $form.find('input[name=name]').val($a.html()).focus();
      $form.find('input[name=key]').val($a.data('key'));
      $form.find('input[name=alias]').val($a.data('alias'));
      $form.find('select[name=increase]').val($a.data('increase'));
      $form.find('select[name=year_start]').val($a.data('year_start'));
      $form.find('select[name=year_finish]').val($a.data('year_finish'));
      $form.find('.remove-wrapper').removeClass('hidden');
    });
  }
  
  // ресеты, скрытия, блокировки
  function resetList(type) {
    switch (type) {
      case 'mark':
        resetForm($modelsForm);
        $modelsForm.addClass('hidden');
        resetForm($yearsForm);
        $yearsForm.addClass('hidden'); 
        list.model.empty().block();
        list.year.empty();
        break;
      case 'model':
        resetForm($yearsForm);
        $yearsForm.addClass('hidden');
        list.year.empty().block();
        break;
      case 'category':
        resetForm($elementsForm);
        $elementsForm.addClass('hidden');
        list.element.empty().block();
        break;
    }
  }

  // инициализаций форм
  function initForms() {
    $('.js-catalog-wrapper form').ajaxForm({
      dataType: 'json',
      success: function (res, statusText, xhr, form) {
        form.find('.has-error').removeClass('has-error');
        form.find('.text-danger').empty();
        form.unblock();
        if (res.success) {
          if (res.message) {
            Brevis.Message.success(res.message);
          }
          form.find('button[type="reset"]').click();
          var type = form.data('type');
          // обновление и инициализаци нужного списка
          list[type].html(templateItems(res.data.items, tpl[type]));
          initList(list[type]);
          if (res.data.select != '') {
            list[type].find('a[data-key=' + res.data.select + ']').click();
          } else {
            resetList(type);
          }
          if (type == 'increase') {
            // reset options in element increase select
            var $select = $elementsForm.find('select');
            $select.empty();
            var options = $.parseJSON(res.data.items);
            $.each(options, function(i, el){
              $select.append('<option value="' + el.id + '">' + el.increase + '</option>');
            });
            // нужно перегрузить выделенную категорию
            list.category.find('li.selected a').click();
          }
        } else {
          if (res.message) {
            Brevis.Message.error(res.message);
          }
          $.each(res.data['errors'], function (index, value) {
            var $input = form.find('[name="' + index + '"]');
            $input.parent('.form-group').addClass('has-error');
            $input.siblings('.text-danger').html(value);
          });
        }
      }
      ,beforeSubmit: function(arr, $form, options) {
        $form.block();
        return true;
      }
    })
    .find('button[type="reset"]').on('click', function(e){
      // нельзя ресетить формы, слетают ключи
      e.preventDefault();
      var $form = $(this).parent('form');
      resetForm($form);
    });
    $('.js-catalog-wrapper form').find('input[name="remove"]').on('change', function(e){
      var $button = $(this).parents('form').find('button[type="submit"]');
      if ($(this).prop('checked')) {
        $button.addClass('btn-danger');
      } else {
        $button.removeClass('btn-danger');
      }
    });
  }
  
  /**
   * Шаблонизация ответа
   * @param {json} items
   * @param {string} template
   * @returns {String}
   */
  function templateItems(items, template) {
    var html = [];
    items = $.parseJSON(items);
    $.each(items, function(i, el){
      if (typeof(el.allow_remove) != 'undefined') {
        el.allow_remove = el.allow_remove == '1';
      }
      html.push(template.render(el));
    });
    return html.join('')
  }
  
  function resetForm($form) {
    $form.parents('li').removeClass('active');
    $form.appendTo($form.parents('div.js-catalog-list'));
    $form.find('input[name=key], input[name=name], input[name=alias]').val('');
    $form.find('input[name=remove]').attr('checked', false); //.addClass('hidden');
    $form.find('select[name=increase]').val(1);
    $form.find('select[name=year_start]').val(0);
    $form.find('select[name=year_finish]').val(0);
    $form.find('.has-error').removeClass('has-error');
    $form.find('.btn-danger').removeClass('btn-danger');
    $form.find('.text-danger').html('');
    $form.find('.remove-wrapper').addClass('hidden');
  }

  var stickyOffset = $('header').outerHeight();
  $('.active .catalog-wrapper > div').stick_in_parent({
    offset_top: stickyOffset
  });
  $('.js-tab-container').bind('easytabs:after', function(event, $clicked, $targetPanel, settings) {
    $(document.body).trigger("sticky_kit:detach");
    $targetPanel.find('.catalog-wrapper > div').stick_in_parent({
      offset_top: stickyOffset
    });
  });
  initList(list.mark);
  initList(list.model);
  initList(list.year);
  initList(list.category);
  initList(list.element);
  initList(list.increase);
  initList(list.bodytype);
  initForms();
    
});