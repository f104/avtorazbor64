<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{block 'title'}{$pagetitle}{/block}</title>
    <meta name="description" content="{block 'desc'}{$description|e ?: 'Авторазбор Авангард автозапчасти для любых иномарок Б/У. Выгодно. Жми!!!'}{/block}">
    <meta name="keywords" content="{block 'keywords'}{$keywords|e ?: 'avangard, avangart, awangard, авангард'}{/block}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
{*    <meta http-equiv="last-modified" content="{$_controller->last_modified}" />*}
    <base href="{$_core->baseUrl}">
    <link rel="icon" type="image/png" href="assets/images/favicon-16.png">
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="apple-touch-icon-precomposed" href="assets/images/favicon-57.png">
    {block 'css'}
      {var $css = [
        '/assets/css/magnific-popup.css',
        '/assets/css/jquery.jgrowl.min.css',
        '/assets/js/bootstrap-datepicker/bootstrap-datepicker.min.css',
        '/assets/fonts/Lato/latofonts.css',
        '/assets/fonts/font-awesome-4.7.0/css/font-awesome.css',
        '/assets/css/cars/cars.css',
        '/assets/css/animate.css',
        '/assets/css/common.css',
        ]}
      {if $_core->useMunee}
        <link rel="stylesheet" href="{$css|join}">
        {if $_controller->additionalCSS}
          <link rel="stylesheet" href="{$_controller->additionalCSS|join}">
        {/if}
      {else}
        {foreach $css as $cssItem}
          <link rel="stylesheet" href="{$cssItem}">
        {/foreach}
        {foreach $_controller->additionalCSS as $cssItem}
          <link rel="stylesheet" href="{$cssItem}">
        {/foreach}
      {/if}
    {/block}
  </head>
  <body>
    
    <header>
      <div class="header-strip">
        <div class="container-fluid">
          <div class="logo">
            <a href="{$_core->baseUrl}">
              <img src="assets/images/logo.png?v=2" alt="Авторазбор Авангард">
            </a>
          </div>
          <div>Авторазбор Авангард Саратов – запчасти для иномарок б/у в наличии в Саратове</div>
          {block 'header-contacts'}
            <ul class="header-contact">
              <li>
                <span>Саратов</span>
                <a href="tel:+79518857676" class="header-phone wow flash" data-wow-duration="2s" data-wow-iteration="1">+79518857676</a>
              </li>
              {*
              <li>
                {if $_core->cityKey == 'moscow'}
                  <span>Москва</span>
                {else}
                  <a href="/moscow/">Москва</a>
                {/if}
                  <a href="tel:+79688057800" class="header-phone wow flash" data-wow-duration="2s" data-wow-iteration="1">+79688057800</a>
              </li>
              *}
              {if $_controller->uri != 'feedback'}<li><a href="feedback"><i class="fa fa-envelope-o"></i> Напишите нам</a></li>{/if}
            </ul>
          {/block}
        </div>
      </div>
      <div class="container-fluid">
        <nav>
          <div class="nav-toggler"><i class="fa fa-bars fa-2x" aria-hidden="true"></i></div>
          <div class="nav-collapse">
            {*block 'header-contacts'}{/block*}
            <menu>
              {foreach $_controller->getMenu() as $name => $page}
                {if $_controller->uri == $name}
                  <li class="active">{$page.title}</li>
                {else}
                  <li><a href="{$page.link}">{$page.title}</a></li>
                {/if}
              {/foreach}
              {if $_controller->uri != 'help'}
                <li><a href="help?resource={$_controller->uri}" class="mp-ajax-popup-align-top link-clear" title="Помощь"><i class="fa fa-question-circle"></i></a></li>
              {/if}
            </menu>
            <form id="search_form" class="search-form js-ajaxform" method="get" action="search">
              <input name="sq" type="text" placeholder="Поиск по каталогу" title="Марка автомобиля (например, Mercedes), код производителя (например, MR646805) или продавца (например, A9A1N10FA4700001)">
              <button id="search_form_button" type="submit"><i class="fa fa-search fa-lg"></i></button>
            </form>
            {if $_controller->uri != 'user'}
              <div class="user">
                {if $authUser}
                  <a href="user"><i class="fa fa-user-circle fa-lg"></i> Личный кабинет</a>
                  <a class="user-logout" href="user/logout?returnUri={$_controller->getLogoutReturnUri()}" title="Выйти"><i class="fa fa-sign-out fa-lg"></i></a>
                {else}
                <a href="#auth_form" class="js-inlinepopup"><i class="fa fa-sign-in fa-lg"></i> Вход</a>&nbsp;&nbsp;<a href="user"><i class="fa fa-id-card"></i> Регистрация</a>
                {/if}
              </div>
            {/if}
          </div>
        </nav>
      </div>
      {if $authUser}
        {set $pages = $_controller->getUserMenu()}
        <div class="user-nav">
          <div class="container-fluid">
            <ul>
              {foreach $pages as $uri => $page}
                <li
                {if $_controller->uri == $uri}
                  class="active"><span>{$page.title}</span>
                {else}
                  ><a href="{$uri}">{$page.title}</a>
                {/if}
                {if $page.submenu?}
                  <ul>
                    {foreach $page.submenu as $subUri => $subPage}
                      <li
                      {if $_controller->uri == $uri ~ '/' ~ $subUri}
                        class="active"><span>{$subPage.title}</span>
                      {else}
                        ><a href="{$uri}/{$subUri}">{$subPage.title}</a>
                      {/if}
                      </li>
                    {/foreach}
                  </ul>
                {/if}
                </li>
              {/foreach}
              {if $_controller->uri != 'help'}
                <li><a href="help?article=supplier&resource={$_controller->uri}" class="mp-ajax-popup-align-top link-clear" title="Помощь"><i class="fa fa-question-circle"></i></a></li>
              {/if}
            </ul>
          </div>
        </div>
      {/if}
    </header>
    
    <div class="main">
      
      <div class="container-fluid">
        {if $_controller->uri != '/'}
          <ol class="breadcrumb hidden-print">
            {foreach $_controller->breadcrumbs as $k => $v}
              <li><a href="{$k}">{$v}</a></li>
            {/foreach}
          </ol>
        {/if}
        <article>
          <h1 class="title">
            {block 'h1'}{$title}{/block}
          </h1>
          {if $message}
            <p class="alert alert-{$success == true ? 'success' : 'danger'}">{$message}</p>
          {/if}
          {block 'content'}
            {$content}
          {/block}            
        </article>
      </div>
      {block 'feedback'}
        {*include '_feedback.form.tpl'*}
      {/block}
    </div> <!-- /.main -->
    
    <footer>
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-9">
            <ul class="footer-menu">
              <li><a href="{$_core->baseUrl}">Главная</a></li>
              <li><a href="info">Оплата и доставка</a></li>
              <li><a href="contact">Контакты</a></li>
            </ul>
            <ul class="footer-menu">
              <li><a href="agree">Соглашение об обработке персональных данных</a></li>
              <li><a href="privacy">Политика конфиденциальности</a></li>
              <li><a href="offer">Договор-оферта</a></li>
            </ul>
            <ul class="footer-menu">
              <li><p>Мы в социальных сетях:</p></li>
              <ul class="social">
                <li><a href="https://vk.com/club152558548"><i class="fa fa-vk"></i></a></li>
                <li><a href="https://www.facebook.com/%D0%90%D0%B2%D1%82%D0%BE%D1%80%D0%B0%D0%B7%D0%B1%D0%BE%D1%80-%D0%90%D0%B2%D0%B0%D0%BD%D0%B3%D0%B0%D1%80%D0%B4-475343969487636/"><i class="fa fa-facebook"></i></a></li>
              </ul>
            </ul>
            <ul class="payments">
              <li class="visa">visa</li>
              <li class="mc">mc</li>
              <li class="wm">wm</li>
              <li class="qiwi">qiwi</li>
              <li class="ym">ym</li>
              <li class="alfa">alfa</li>
              <li class="mir">mir</li>
            </ul>
          </div>
          <div class="col-sm-3">
            <div class="footer-logo">
              <p><a class="link-clear" href="{$_core->baseUrl}"><img src="assets/images/logo.png?v=2" alt="Авторазбор Авангард"></a></p>
              <p>Авторазбор иномарок &laquo;Авангард&raquo;<br>&copy; 2016-{$.php.date('Y')}</p>
            </div>
          </div>
        </div>
      </div>
    </footer>
    <a href="#" id="scrollup"><i class="fa fa-arrow-up"></i></a>
      
    {block 'js'}
{*          <script src="/assets/js/bootstrap.min.js"></script>*}
      {var $js = [
        '/assets/js/jquery-2.1.4.min.js',
        '/assets/js/bootstrap.min.js',
        '/assets/js/jquery.magnific-popup.min.js',
        '/assets/js/jquery.form.min.js',
        '/assets/js/jquery.jgrowl.min.js',
        '/assets/js/jquery.blockUI.js',
        '/assets/js/bootstrap-datepicker/bootstrap-datepicker.min.js',
        '/assets/js/bootstrap-datepicker/bootstrap-datepicker.ru.min.js',
        '/assets/js/scrollup.js',
        '/assets/js/jquery.easytabs.min.js',
        '/assets/js/jquery.confirm.min.js',
        '/assets/js/libs/jquery.sticky-kit.min.js',
        '/assets/js/libs/wow.min.js',
        '/assets/js/common.js',
        ]}
      {if $_core->useMunee}
        <script src="{$js|join}"></script>
        {if $_controller->additionalJs}
          <script src="{$_controller->additionalJs|join}"></script>
        {/if}
      {else}
        {foreach $js as $jsItem}
          <script src="{$jsItem}"></script>
        {/foreach}
        {foreach $_controller->additionalJs as $jsItem}
          <script src="{$jsItem}"></script>
        {/foreach}
      {/if} 
    {/block}
    {if $_controller->formAuth?}{$_controller->formAuth->draw()}{/if}
    {if $_core->useStat}{include '_counters.tpl'}{/if}
    {*if !$_core->isAuth or !$_core->authUser->isManager()}{include '_1C.tpl'}{/if*}
  </body>
</html>