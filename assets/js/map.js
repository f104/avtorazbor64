$(document).ready(function() {
  
  var placemarks = [];
  $('.js-map-geo').each(function(){
    var data = $(this).data('geo'),
        name = $(this).data('name'),
        body = $(this).html();
    var geo = data.split(',');
    geo[0] = parseFloat(geo[0]);
    geo[1] = parseFloat(geo[1]);
    placemarks.push({
      geo: geo,
      name: name,
      body: body
    });
  });  

  // show map
  showMap = function() {
    ymaps.ready(function () {
      var map = new ymaps.Map('map', {
        center: [51.537347,46.004802]
        , zoom: 11
        , controls: ['default']
      }),
      geoobjects = [];
      for (var i = 0, len = placemarks.length; i < len; i++) {
        geoobjects[i] = new ymaps.Placemark(placemarks[i].geo, {
          balloonContent: placemarks[i].body,
          balloonContentFooter: placemarks[i].name
        },{
          preset: 'islands#darkGreenIcon'
        });
        map.geoObjects.add(geoobjects[i]);
      }
//      map.behaviors.disable('scrollZoom');
      map.setBounds(map.geoObjects.getBounds(), {
        checkZoomRange: true
      });
//      openBalloon(map, 0);
      $('.js-map-geo').each(function(i){
        $(this).click(function(e){
//          e.preventDefault();
          openBalloon(map, i);
        });
      });
    });
  }
  
  openBalloon = function(map, index) {
    var pl = map.geoObjects.get(index);
    if (pl) {
      pl.balloon.open();
      var coords = pl.geometry.getCoordinates();
//      console.log(coords);
      map.zoomRange.get(coords).then(function (range) {
//        console.log(range);
        map.setCenter(coords, 18, {
          checkZoomRange: true
        });
//          map.setCenter([46.025772, 51.574705], range[1], {
//              checkZoomRange: true
//          });
      });
    }
  }
  
  if (placemarks.length != 0) {
    $('#map').addClass('map');
    $.getScript("https://api-maps.yandex.ru/2.1/?lang=ru_RU")
      .done(function( script, textStatus ) {
//        console.log(placemarks);
        showMap();
//        $('.js-map-geo')[0].click();
      })
      .fail(function( jqxhr, settings, exception ) {
        $('#map').text('Не могу загрузить карту.');
      });
  }
});