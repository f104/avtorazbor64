/* scroll up */
if ($('#scrollup') && $('#scrollup').css('visibility') != 'hidden') {
    
  (function ($) {

    $(function(){

      var $win = $(window),
          $scrollup = $('#scrollup'),
          offset = 400;
          
      processScrollUp();

      $scrollup.click(function(event) {
        event.preventDefault();
        $('html,body').animate({ scrollTop: 0 });
        return false;
      });
        
      $win.on('scroll', processScrollUp);

      function processScrollUp() {
        var scrollTop = $win.scrollTop();

        if (scrollTop >= offset) {
          $scrollup.fadeIn();
        } else {
          $scrollup.fadeOut();
        }
      }

    });

  })(window.jQuery);

}