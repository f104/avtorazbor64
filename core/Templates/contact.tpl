{extends '_base.tpl'}

{block 'desc'}Адреса офисов и складов, режим работы, карта.{/block}

{block 'content'}
  
  <div class="row">
  <div class="col-md-6">

    <div class="panel panel-info">
      <div class="panel-body">E-mail: <a href="mailto:avgmsk@inbox.ru">avgmsk@inbox.ru</a><br>
        E-mail директора: <a href="mailto:onlinezakazsar@mail.ru">onlinezakazsar@mail.ru</a></div>
    </div>

    <div class="panel-group">
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><span class="dotted js-map-geo" data-geo="51.574705,46.025772" data-name="ул. Танкистов 90 А">Представительство в Саратове</span></h4>
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
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><span class="dotted js-map-geo" data-geo="55.945720,37.787586" data-name="ул. 3-я Новая, 30">Представительство в Москве</span></h4>
        </div>
        <div class="panel-collapse">
          <div class="panel-body">
            Адрес:  Россия, Московская область, г. Мытищи, ул. 3-я Новая, 30.<br>
            Телефон: +7 9688057800<br>
            График работы:<br>
            Пн-пт: 9.00-18.00<br>
          </div>
        </div>
      </div>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><span class="dotted js-map-geo" data-geo="54.810087,56.115770" data-name="ул. Машиностроителей, 21/1">Представительство в Уфе</span></h4>
        </div>
        <div class="panel-collapse">
          <div class="panel-body">
            Адрес:  Россия, г. Уфа, <span class="text-nowrap">ул. Машиностроителей, 21/1</span>.<br>
            Телефон: +7 9872425766<br>
            График работы:<br>
            Пн-пт: 9.00-18.00<br>
          </div>
        </div>
      </div>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><span class="dotted js-map-geo" data-geo="53.229844,44.924395" data-name="проспект Победы, 140">Представительство в Пензе</span></h4>
        </div>
        <div class="panel-collapse">
          <div class="panel-body">
            Адрес:  Россия, г. Пенза, <span class="text-nowrap">проспект Победы, 140</span>.<br>
            Телефон: +7 9022074666, +7 (8412) 774 666<br>
            График работы:<br>
            Пн-пт: 9.00-18.00<br>
          </div>
        </div>
      </div>
      <div class="panel panel-default">
        <div class="panel-heading">
          <h4 class="panel-title"><span class="dotted js-map-geo" data-geo="54.307422,48.319860" data-name="Московское шоссе, 80а">Представительство в Ульяновске</span></h4>
        </div>
        <div class="panel-collapse">
          <div class="panel-body">
            Адрес:  Россия, г. Ульяновск, <span class="text-nowrap">Московское шоссе, 80а</span>.<br>
            Телефон: +7 9033381333<br>
            График работы:<br>
            Пн-пт: 9.00-18.00<br>
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