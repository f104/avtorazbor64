$(document).ready(function () {
  
  function Manage() {
    // switchon checkbox
    $('.js-sklad-switchon-checkbox').change(function(){
      var $checkbox = $(this);
      var href = 'sklads/switchon?id=' + $checkbox.data('id') + '&switchon=' + ($checkbox.prop('checked') ? 1 : 0);
      $.getJSON(href, function(res) {
        if (!res.success) {
          $checkbox.attr('checked', !$checkbox.prop('checked'));
        }
        $.jGrowl(res.message);
      });
    });
  };
    
  Manage();
  
});