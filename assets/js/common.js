Brevis = {
  /**
   * Подключает popup-галерею
   * @param string jquery-selector
   * @returns void
   */
  popupGallery: function (selector) {
    $(selector).each(function (i) {
      $(this).magnificPopup({
        delegate: 'a',
        type: 'image',
        tLoading: 'Loading image #%curr%...',
        mainClass: 'mfp-img-mobile',
        gallery: {
          enabled: true,
          navigateByImgClick: true,
          preload: [0, 1] // Will preload 0 - before current, and 1 after the current image
        },
        image: {
          tError: '<a href="%url%">The image #%curr%</a> could not be loaded.',
          titleSrc: function (item) {
            return item.el.attr('title');
          }
        }
      });
    });
  },
  // ajaxForm 
  ajaxForm: function (selector) {
    $(selector).ajaxForm({
      dataType: 'json',
      success: function (res, statusText, xhr, form) {
        form.find('.has-error').removeClass('has-error');
        form.find('.text-danger').empty();
        form.unblock();
        if (res.success) {
          $.magnificPopup.close();
          if (res.message) {
            Brevis.Message.success(res.message);
          }
          // обновление, форма была вызвана в popup-окне
          if (typeof(res.data['update']) == 'object') {
            $.each(res.data['update'], function (index, value) {
              $(index).html(value);
              Brevis.blinker(index);
            });
          }
        } else if (res.data['redirect']) {
          window.location = res.data['redirect'];
        } else {
          if (res.message) {
            Brevis.Message.error(res.message);
          }
          if (typeof(res.data['update']) == 'undefined') {
            $.each(res.data['errors'], function (index, value) {
              var $input = form.find('[name="' + index + '"]');
              $input.parent('.form-group').addClass('has-error');
              $input.next('.text-danger').html(value);
            });
          }
        }
        $(document).trigger('af_complete', [form, res]);
//        console.log(res);
      }
      ,beforeSubmit: function(arr, $form, options) {
        $form.block();
        return true;
      }
      ,error: function() {
        $(selector).unblock();
      }
    }).on('reset', function(){
      $(this).find('.has-error').removeClass('has-error');
      $(this).find('.text-danger').empty();
    });
  },
  // подсветка элемента selector count раз
  blinker: function(selector, count) {
    if (typeof(count) == 'undefined') { count = 2; }
    var $el = $(selector);
    if ($el.length != 0) {
      var int = setInterval(blink, 900);
    }
    function blink() {
      $el.animate({
          opacity: .5
        }, 300, function(){
          $el.animate({
            opacity: 1
          }, 300);
        });
      count--;
      if (count == 0) {
        clearInterval(int);
      }
    }
  },
  datepicker: function(selector) {
    $(selector).datepicker({
      format: "dd-mm-yyyy",
      weekStart: 1,
      todayBtn: "linked",
      language: "ru",
      autoclose: true,
      todayHighlight: true
    });
  },
  // ajax popup
  ajaxPopup: function (selector) {
    $(selector).magnificPopup({
      type: 'ajax',
      callbacks: {
        parseAjax: function(mfpResponse) {
          mfpResponse.data = JSON.parse(mfpResponse.data).data.content;
//          console.log('Ajax content loaded:', mfpResponse);
        },
        ajaxContentAdded: function() {
          // Ajax content is loaded and appended to DOM
//          console.log(this.content);
          Brevis.ajaxForm('.mfp-content .js-ajaxform');
          Brevis.datepicker('.mfp-content .js-datepicker');
          this.content.find('form a.btn').click(function(e){
            // кнопка отмена
            e.preventDefault();
            $.magnificPopup.close();
          });
        }
      }
    });
  },
  // inline popup
  inlinePopup: function (selector) {
    $(selector).parents('form').find('[type="reset"]').click(function(){
      $.magnificPopup.close();
    });
    $(selector).magnificPopup({
      type: 'inline',
      callbacks: {
        open: function() {
          this.content.find('[type="reset"]').click(function(){
            $.magnificPopup.close();
          });
        }
      }
    });
  },
  
  /**
   * Уменьшает на единицу нумерацию строк и общее кол-во элементов.
   * Используется при удалении строк таблицы.
   * @param jquery $table
   * @param string curIndex Номер удаленной строки
   * @returns void
   */
  recalcTableRows: function ($table, curIndex) {
    curIndex = parseInt(curIndex, 10) - 1;
    $table.find('.js-table-row-index').each(function(i) {
      if (i >= curIndex) {
        var index = parseInt($(this).html(), 10) - 1;
        $(this).html(index);
      }
    });
    $table.find('.js-table-row-count').each(function(i) {
      var index = parseInt($(this).html(), 10) - 1;
      $(this).html(index);
    });
  },
  
  /**
   * Инициализация кнопки и формы оформления заказа покупателем
   * @returns void
   */
  initBuy: function() {
    $('.js-button-buy').click(function(e){
      var $button = $(this);
      $.magnificPopup.open({
        items: {
          src: '#buy_form',
          type: 'inline'
        },
        callbacks: {
          open: function() {
            $('#buy_form_id').val($button.data('id'));
            $('#buy_form_price').html($button.data('price'));
            // кнопка "Отмена"
            $('#buy_form button[type=reset]').click(function(e){ 
              $.magnificPopup.close();
            });
          }
        }
      });
    });
  },
  
  /**
   * Инициализация кнопки и формы запроса фотографии
   * @returns void
   */
  initRequestPhoto: function() {
    $('.js-button-requestphoto').click(function(e){
      var $button = $(this);
      $.magnificPopup.open({
        items: {
          src: '#requestphoto_form',
          type: 'inline'
        },
        callbacks: {
          open: function() {
            if ($button.data('id')) {
              $('#requestphoto_form_id').val($button.data('id'));
            }
            // кнопка "Отмена"
            $('#requestphoto_form button[type=reset]').click(function(e){ 
              $.magnificPopup.close();
            });
          }
        }
      });
    });
  },
  
  initHauth: function() {
    $('.ha-auth').each(function(i){
      var $this = $(this);
      var $links = $(this).find('a.ha');
      $(this).parents('form').find('input[name=rememberMe]').change(function(e){
        $this.data('remember', $(this).prop('checked') ? 1 : 0);
      });
      $this.data('return', $(this).parents('form').find('input[name=returnUri]').val());
      $links.click(function(e){
        e.preventDefault();
        var href = $(this).attr('href') + '&remember=' + $this.data('remember') + 
                '&return=' + encodeURIComponent($this.data('return'));
        window.location = href;
      });
    });
  },

  initTabs: function(selector) {
    $(selector).easytabs();
  },
  
  /* help table of content scroll */
  initMdToc: function($wrapper) {
    $wrapper.find('.js-md-toc a').each(function(index){
      $(this).click(function(e){
        e.preventDefault();
        var href = $(this).attr('href');
        var $target = $('a[name="' + href.substr(href.indexOf('#') + 1) + '"]');
        if ($target.length != 0) {
          $wrapper.animate({
            scrollTop: $target.offset().top - 20
          }, 1000);
        } else {
          console.log('target not found');
        }
      });
    });
    $active = $wrapper.find('.js-md-active').first();
    if ($active.length != 0) {
      $wrapper.animate({
        scrollTop: $active.offset().top - 20
      }, 1000);
    }
  },
  
  /*
  универсальный код для целей
  цели прописываются так внутри объектов
  data-goal-id="F_ACCESS" data-goal-event="submit"
  если  data-goal-event не задано - по умолчанию - click
  */
  initGoals: function () {
    function _reachGoal(goal) {
      var keys = Object.keys(window);
      for (var index in keys) {
        if (keys[index].indexOf('yaCounter') >= 0) {
          break;
        }
      }
      if (index >= 0) {
        if (typeof window[keys[index]] != 'undefined') {
          window[keys[index]].reachGoal(goal);
        }
      }
      if (typeof ga != 'undefined') {
        ga('send', 'event', goal, goal);
      }
    }
    $('[data-goal-id]').each(function () {
      var gid = $(this).attr('data-goal-id');
      var gev = $(this).attr('data-goal-event');
      if (typeof gev == 'undefined') {
        gev = 'click';
      }
      $(this).on(gev, function () {
        _reachGoal(gid);
      });
    });
  }

}
Brevis.Message = {
    success: function (message, sticky) {
        if (message) {
            if (!sticky) {
                sticky = false;
            }
            $.jGrowl(message, {theme: 'brevis-message-success', sticky: sticky});
        }
    },
    error: function (message, sticky) {
        if (message) {
            if (!sticky) {
                sticky = false;
            }
            $.jGrowl(message, {theme: 'brevis-message-error', sticky: sticky});
        }
    },
    info: function (message, sticky) {
        if (message) {
            if (!sticky) {
                sticky = false;
            }
            $.jGrowl(message, {theme: 'brevis-message-info', sticky: sticky});
        }
    },
    close: function () {
        $.jGrowl('close');
    },
};

$(document).ready(function () {
  
  new WOW().init();
  
  (function($) {
    // nav toggler
    $('.nav-toggler').click(function(event) {
      var $nav = $('.nav-collapse');
      $nav.hasClass('opened') ? $nav.slideUp() : $nav.slideDown();
      $nav.toggleClass('opened');
    });
  })(jQuery);
  
  // fix header
  var headerHeight = $('header').outerHeight();
  $('header').css('position', 'fixed');
  $('.main').css('margin-top', headerHeight);

  $('.js-menu-collapse-toggler').click(function (e) {
    e.preventDefault();
    $menu = $('.top-menu');
    $menu.is(':hidden') ? $menu.show() : $menu.hide();
  });
  // нужно, чтобы не пропадало меню и стили не слетали
  $(window).resize(function () {
    $('.top-menu').removeAttr('style');
  });

//  if ($('.top-menu').is(':hidden')) {
//    $('#search_form_button').addClass('opened');
//    $('.search-form input').removeClass('hidden');
//  } else {
//    $('#search_form_button').click(function (event) {
//      $(this).toggleClass('opened')
//      $('.search-form input').toggleClass('hidden');
//      if ($(this).hasClass('opened')) {
//        $('.search-form input').focus();
//      }
//    });
//    $(document.body).click(function (e) {
//      var $box = $('#search_form');
//      if (e.target.id !== 'search_form' && !$.contains($box[0], e.target)) {
//        $('#search_form_button').removeClass('opened')
//        $('.search-form input').addClass('hidden');
//      }
//    });
//  }


  $('.toggler').click(function (e) {
    e.preventDefault();
    var target = $(this).data('toggle');
    $('.toggler-content').not(target).addClass('hidden');
    $(target).toggleClass('hidden');
  });


  $('.js-hidden').hide();
  $('.js-submit').change(function () {
    $(this).parents('form').submit();
  });

  $('#cars_nav_form select').change(function () {
    $('#cars_nav_form').submit();
  });
  
  $('.sticky').stick_in_parent({
      offset_top: 20
    });

  $.blockUI.defaults.message = null;
//  $(document).ajaxStart($.blockUI).ajaxStop($.unblockUI);

  Brevis.popupGallery('.js-popup-gallery');
  Brevis.ajaxForm('.js-ajaxform');
  Brevis.ajaxPopup('.js-ajaxpopup');
  Brevis.inlinePopup('.js-inlinepopup');
  Brevis.datepicker('.js-datepicker');
  Brevis.initBuy();
  Brevis.initRequestPhoto();
  Brevis.initHauth();
  Brevis.initTabs('.js-tab-container');
  Brevis.initMdToc($('body'));
  Brevis.initGoals();

  function loadPage(href, $cnt) {
    if (typeof $cnt == 'string') {
      $cnt = $($cnt);
    }
    $.getJSON(href, function (res) {
      if (res.success) {
        $cnt.empty().html(res.data.html);
        $('html, body').animate({scrollTop: $cnt.offset().top});
        ajaxLinks($cnt);
        $(document).trigger('ajaxlinks_load');
      } else {
        console.log(res);
      }
    });
  }

  var oldBrowser = !(window.history && history.pushState);
  if (!oldBrowser) {
    // Добавляем в историю текущую страницу при первом открытии раздела
    history.replaceState({ajaxlinks: {url: window.location.href, cnt: '#ajaxwrapper'}}, '');
  }

  $(window).on('popstate', function (e) {
    // Проверяем данные внутри события, и если там наш pagination
    if (e.originalEvent.state && e.originalEvent.state['ajaxlinks']) {
      // То загружаем сохранённую страницу
      loadPage(e.originalEvent.state['ajaxlinks'].url, e.originalEvent.state['ajaxlinks'].cnt);
    }
  });

  // ajaxLink 
  function ajaxLinks($cnt) {
    $cnt.find('.js-ajaxlink').click(function (event) {
      event.preventDefault();
      var href = $(this).attr('href');
      var $wrapper = $(this).parents('.js-ajaxwrapper');
      var wrapperID = $wrapper.attr('id');
      if (href && $cnt) {
        if (wrapperID) {
          window.history.pushState({ajaxlinks: {url: href, cnt: '#' + wrapperID}}, '', href);
        }
        loadPage(href, $wrapper);
      }
    });
  }
  ajaxLinks($('body'));

  /* password toggler */
  $('.password-view-wrapper > i').click(function () {
    var $parent = $(this).parent();
    var $input = $parent.find('input');
    $parent.toggleClass('eye-closed eye-open');
    if ($parent.hasClass('eye-open')) {  
      $input.attr('type', 'text').attr('autocomplete', 'off'); 
      $(this).removeClass('fa-eye-slash').addClass('fa-eye').attr('title', 'Скрыть пароль');
    } else { 
      $input.attr('type', 'password'); 
      $(this).removeClass('fa-eye').addClass('fa-eye-slash').attr('title', 'Показать пароль');
    }
  });

  // cars ajax response
  $('#cars_ajax_content a').on('click', function () {
    var href = $(this).attr('href'); // Определяем ссылку
    // Пустые ссылки не обрабатываем
    if (href != '') {
      // Для индикации работы через ajax делаем элемент-обёртку полупрозрачным
      var $wrapper = $('#cars_ajax_content');
      $wrapper.css('opacity', .5);
      // Запрашиваем страницу через ajax
      $.get(href, function (res) {
        // При получении любого ответа делаем обёртку обратно непрозрачной 
        $wrapper.css('opacity', 1);
        // Получен успешный ответ
        if (res.success) {
          // Меняем содержимое
          $wrapper.html(res.data['content']);
        }
        // Ответ с ошибкой и в массиве данных указан адрес перенаправления
        else if (res.data['redirect']) {
          // Редиректим пользователя
          window.location = res.data['redirect'];
        }
        // Иначе пишем ошибку в консоль и больше ничего не делаем
        else {
          console.log(res);
          // А вообще, здесь можно и вывести ошибку на экран
          // alert(res.data['message']);
        }
      }, 'json');
    }
    // В любом случае не даём перейти по ссылке - у нас же тут ajax пагинация
    return false;
  });

  if ($.fn.typeahead) {
    $('.js-typeahead-city').wrap('<div></div>').typeahead({
      minLength: 2,
      highlight: true,
      hint: false
    },
    {
      name: 'city',
      display: 'name',
      limit: 10,
      source: function(query, syncResults, asyncResults) {
        var region_id= $('select[name=region_id]').val();
        $.get('user/cities?country_id=1&region_id='+region_id+'&sq=' + query, function(data) {
          asyncResults(data);
        }, 'json');
      }
    });
  }
  
  $('.mp-ajax-popup-align-top').magnificPopup({
    type: 'ajax',
    alignTop: true,
//    overflowY: 'scroll',
//    settings: {dataType: 'json'},
    callbacks: {
      parseAjax: function(mfpResponse) {
        var data = jQuery.parseJSON(mfpResponse.data);
        mfpResponse.data = data.data.html;
        $(mfpResponse.data).addClass('white-popup');
        mfpResponse.data = $(mfpResponse.data).find('article');
      },
      ajaxContentAdded: function() {
        Brevis.initMdToc($('.mfp-wrap'));
      }
    }
  });
  
  /* register form */
  $('#register_form input[type="radio"][value="1"]').change(function(ev){
    $('#register_form input[name="ogrn"]').parent('div').addClass('hidden');
  });
  $('#register_form input[type="radio"][value="3"]').change(function(ev){
    $('#register_form input[name="ogrn"]').parent('div').removeClass('hidden');
  });

});