{extends '_base.tpl'}

{block 'desc'}Адреса офисов и складов, режим работы, карта.{/block}

{block 'content'}
  
  <div class="row">
  <div class="col-md-6">

    <div class="panel panel-info">
      <div class="panel-body">E-mail: <a href="mailto:razbor.64@mail.ru">razbor.64@mail.ru</a><br>
        E-mail директора: <a href="mailto:onlinezakazsar@mail.ru">onlinezakazsar@mail.ru</a></div>
    </div>

    <div class="panel-group">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><span class="dotted js-map-geo" data-geo="51.456740,45.910199" data-name="ул. Азина, 50">Склад</span></h4>
        </div>
        <div class="panel-collapse">
          <div class="panel-body">
            Адрес: Россия, г. Саратов, ул. Азина, 50
            (в навигаторе забивайте Азина 52, мы с другой стороны этого здания)<br>
            Телефон: +7 9042417676<br>
            График работы:<br>
            Пн-сб: 9.00-18.00
          </div>
        </div>
      </div>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><span class="dotted js-map-geo" data-geo="51.574705,46.025772" data-name="ул. Танкистов 90 А">Представительство</span></h4>
        </div>
        <div class="panel-collapse">
          <div class="panel-body">
            Адрес: 410047, Россия, г. Саратов, ул. Танкистов 90 А. Въезд в базу, вход справа от ворот. 
            Пересечение с ул. Техническая, поворот на кондитерскую фабрику.<br>
            Телефон: +7 9518857676<br>
            График работы:<br>
            Пн-пт: 8.00-19.00<br>
            Сб: 10.00-17.00<br>
            Вс: 10.00-15.00
          </div>
        </div>
      </div>
    </div>
    
  </div>
  <div class="col-md-6">

    <div id="map"></div>
    
  </div>
  </div> 
      
  
{/block}

{block 'js'}
  {parent}
  <script src="/assets/js/map.js"></script>
{/block}