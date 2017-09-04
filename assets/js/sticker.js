$(document).ready(function () {
  
  /* duplicate sticker */
  $('.js-duplicate-sticker').click(function(event){
    $('.sticker:first').clone().appendTo('.sticker-wrapper');
  });
  
});