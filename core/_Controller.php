<?php

    namespace Brevis;

    use \Exception as Exception;
    use Kilte\Pagination\Pagination as Pagination;

    class Controller {

        /** @var Core $core */
        public $core;

        /** @var string $name */
        public $name;

        /** @var string $pagetitle <title>*/
        public $pagetitle;

        /** @var string $description <description>*/
        public $description;

        /** @var string $keywords meta name="keywords" */
        public $keywords;

        /** @var string $uri */
        public $uri;

        /** @var array $lang */
        public $lang;

        /** @var string Название словаря для загрузки */
        public $langTopic = null;

        // true для контроллеров, которые испоьзуются только для проверки прав и редиректят на другой контроллер
        // не добавляются в хлебные крошки
        public $onlyRedirect = false;


        /** @var array $breadcrumbs */
        public $breadcrumbs = [];

        /** @var string $template */
        public $template = '_base';

        /** @var array $permissions*/
        public $permissions = [];

        /** @var bool $isAjax */
        public $isAjax = false;

        /** @var string $last_modified Дата последнего изменения из настроек */
//        public $last_modified;

        /** * @var bool $success для отслеживания результатов выполнения и сообщений форм */
        public $success = false;
        public $message;

        /** @var int ID группы поставщиков */
        public $suppliersGroupID = 3;
        /** @var int ID группы покупателей */
        public $buyerGroupID = 1;

        /** @var array Разрешенные фильтры (используется в табличных данных) */
        public $allowedFilters = [];
        /** @var array Установленные фильтры (используется в табличных данных) */
        public $filters = [];
        /** @var array Разрешенные поля для сортировки (используется в табличных данных) */
        public $allowedSort = [];
        /** @var string Поле для сортировки по умолчанию (используется в табличных данных) */
        public $defaultSort = 'name';
        /** @var string Установленное поле для сортировки (используется в табличных данных) */
        public $sortby = '';
        /** @var string Направление сортировки (используется в табличных данных) */
        public $sortdir = 'ASC';

        /** @var array Общие условия для выборки данных */
        public $where = [];
        /** @var string Класс для выборки данных */
        public $classname = null;
        
        /** @var array Фильтры и сортировка данных, используется при экспорте */
        public $exportFilters = [];
        /** @var array Список колонок для экспорта */
        public $exportColumns = [];

        /** @var Brevis/Components/EventLogger $eventLogger */
        public $eventLogger;

        /**
         * Конструктор класса, требует передачи Core
         *
         * @param Core $core
         */
        function __construct(Core $core) {
            $this->core = $core;
            $this->breadcrumbs[$this->core->baseUrl] = 'Главная';
            // check permissions
            if (!empty($this->permissions)) {
                if (!$this->core->isAuth or !$this->core->authUser->checkPermissions($this->permissions)) {
                    $this->core->sendErrorPage(403);
                }
            }
//            $this->last_modified = file_get_contents(PROJECT_LM_FILE);
            if (empty($this->pagetitle)) { $this->pagetitle = $this->name; }
            // uri ресурса == название класса
            if (empty($this->uri)) {
                $className = explode('\\', get_class($this));
                $className = array_pop($className);
                $this->uri = strtolower($className);
            }
            $this->lang = $this->loadLexicon('common');
            if (!empty($this->langTopic)) {
                if (is_string($this->langTopic)) {
                    $this->langTopic = explode(',', $this->langTopic);
                    $this->langTopic = array_map('trim', $this->langTopic);
                }
                foreach ($this->langTopic as $topic) {
                    $this->lang = array_merge($this->lang, $this->loadLexicon($topic));
                    if (!empty($this->core->cityKey)) {
                        $this->lang = array_merge($this->lang, $this->loadLexicon($topic.'.'.$this->core->cityKey));
                    }
                }
            }
            if (!empty($_REQUEST['raw'])) {
                $this->rawData = true;
            }
        }

        /**
         * @param array $params
         *
         * @return bool or string
         */
        public function initialize(array $params = array()) {
            if (!empty($params)) {
                $className = '\Brevis\Controllers\\' . ucfirst(array_shift($params));
                /** @var Controller $controller */
                if (class_exists($className)) {
                    $controller = new $className($this->core);
                    if (get_parent_class($controller) == get_class($this)) {
//            var_dump($this->uri);
                        $controller->uri = $this->uri . '/' . $controller->uri;
                        $controller->breadcrumbs = $this->breadcrumbs;
                        if (!$this->onlyRedirect) {
                            $controller->breadcrumbs[$this->makeUrl($this->uri, $this->filters)] = $this->name;
//                            $controller->breadcrumbs[$this->uri] = $this->name;
                            $controller->pagetitle = $controller->pagetitle  . ' / ' . $this->pagetitle;
                        }
//            var_dump($controller->breadcrumbs);
                        $controller->isAjax = $this->isAjax;
                        $initialize = $controller->initialize($params);
                        if ($initialize === true) {
                            $response = $controller->run();
                        } elseif (is_string($initialize)) {
                            $response = $initialize;
                        } else {
                            $response = 'Возникла неведомая ошибка при загрузке страницы';
                        }
                        return $response;
                    } else {
                        $this->core->sendErrorPage();
                    }
                } else {
                    $this->core->sendErrorPage();
                }
            }
//            $this->pagetitle = $this->name;
            return true;
        }

        /**
         * Основной рабочий метод
         *
         * @return string
         */
        public function run() {
            return $this->template($this->template, $data, $this);
        }
        
        /**
         * Возвращает имя класса.
         * Полностью (Brevis\Controller\Class), кратко (Class), в нижнем регистре (class).
         * @param bool $short Кратко
         * @param bool $lower В нижнем регистре
         * @return string
         */
        public function getControllerName($short = false, $lower = false) {
            $classname = get_class($this);
            if ($short) {
                $classname = explode('\\', $classname);
                $classname = array_pop($classname);
            }
            if ($lower) {
                $classname = strtolower($classname);
            }
            return $classname;
        }
        
        /**
         * Возвращает имя класса для выборки данных.
         * Полностью (Brevis\Model\Class), кратко (Class), в нижнем регистре (class).
         * @param bool $short Кратко
         * @param bool $lower В нижнем регистре
         * @return string
         */
        public function getDataClassName($short = false, $lower = false) {
            $classname = $this->classname;
            if ($short) {
                $classname = explode('\\', $classname);
                $classname = array_pop($classname);
            }
            if ($lower) {
                $classname = strtolower($classname);
            }
            return $classname;
        }

        /**
         * Шаблонизация
         *
         * @param string $tpl Имя шаблона
         * @param array $data Массив данных для подстановки
         * @param Controller|null $controller Контроллер для передачи в шаблон
         *
         * @return mixed|string
         */
        public function template($tpl, array $data = array(), $controller = null) {
            $output = '';
            if (!preg_match('#\.tpl$#', $tpl)) {
                $tpl .= '.tpl';
            }
            if ($fenom = $this->core->getFenom()) {
                try {
                    $data['_core'] = $this->core;
                    $data['_controller'] = !empty($controller) && $controller instanceof Controller ? $controller : $this;
                    $data['pagetitle'] = $this->pagetitle;
                    $data['description'] = $this->description;
                    $data['keywords'] = $this->keywords;
                    $data['title'] = $this->name;
                    $data['success'] = $this->success;
                    $data['message'] = $this->message;
                    $data['authUser'] = $this->core->authUser;
                    $data['lang'] = $this->lang;
                    $output = $fenom->fetch($tpl, $data);
                } catch (Exception $e) {
                    $this->core->log($e->getMessage());
                }
            }

            return $output;
        }

        /**
         * Возвращает пункты меню сайта
         *
         * @return array
         */
        public function getMenu() {
            return array(
                'cars' => array(
                    'title' => 'Запчасти',
                    'link' => $this->core->baseUrl,
                ),
                'info' => array(
                    'title' => 'Оплата и доставка',
                    'link' => 'info',
                ),
                'contact' => array(
                    'title' => 'Контакты',
                    'link' => 'contact',
                ),
            );
        }

        /**
         * Возвращает пункты меню пользователя
         *
         * @return array
         */
        public function getUserMenu() {
            $menu = [
                // покупатель
                1 => [
                    'orders' => [
                        'title' => '<i class="fa fa-shopping-cart"></i> '.$this->lang['orders'],
                        'permission' => 'orders_view',
                    ],
                    'fees' => [
                        'title' => '<i class="fa fa-rub"></i> '.$this->lang['fees'].' <span class="badge">'.$this->core->authUser->balance.'</span>',
                        'permission' => 'fees_view',
                    ],
                ],
                // администратор
                2 => [
                    'users' => [
                        'title' => '<i class="fa fa-users"></i> '.$this->lang['users'],
                    ],
                    'suppliers' => [
                        'title' => '<i class="fa fa-truck"></i> '.$this->lang['suppliers'],
                    ],
                    'sklads' => [
                        'title' => '<i class="fa fa-building"></i> '.$this->lang['sklads'],
                    ],
                    'items' => [
                        'title' => '<i class="fa fa-car"></i> '.$this->lang['items'],
                    ],
                    'orders' => [
                        'title' => '<i class="fa fa-shopping-cart"></i> '.$this->lang['orders'],
                    ],
//                    'payments' => [
//                        'title' => '<i class="fa fa-rub"></i> '.$this->lang['payments'],
//                    ],
                    'fees' => [
                        'title' => '<i class="fa fa-rub"></i> '.$this->lang['fees'],
                    ],
                    'eventlog' => [
                        'title' => '<i class="fa fa-file-text"></i> '.$this->lang['eventlog'],
                    ],
                ],
                // поставщик
                3 => [
                    'sklads' => [
                        'title' => '<i class="fa fa-building"></i> '.$this->lang['sklads'],
                        'permission' => 'sklads_view',
                    ],
                    'items' => [
                        'title' => '<i class="fa fa-car"></i> '.$this->lang['items'],
                        'permission' => 'items_view',
                    ],
                    'orders' => [
                        'title' => '<i class="fa fa-shopping-cart"></i> '.$this->lang['orders'],
                        'permission' => 'orders_view',
                    ],
                    'user/supplier/info' => [
                        'title' => '<i class="fa fa-address-card-o"></i> '.$this->lang['supplier-info'],
                        'permission' => 'supplier_info_edit',
                    ],
                ],
                // кассир
                4 => [
//                    'payments' => [
//                        'title' => '<i class="fa fa-rub"></i> '.$this->lang['payments'],
//                        'permission' => 'payments_view',
//                    ],
                ],
            ];
            $userMenu = [];
            if ($this->core->isAuth) {
                $permissions = $this->core->authUser->getUserPermissions();
                $group = $this->core->authUser->getUserGroup();
                if ($group == 2) {
                    // администратора не проверяем
                    $userMenu = $menu[2];
                } else {
                    if (array_key_exists($group, $menu)) {
                        foreach ($menu[$group] as $key => $item) {
                            if (in_array($item['permission'], $permissions)) {
                                $userMenu[$key] = $item;
                            }
                        }
                    }
                }
            }
            return $userMenu;
        }

        /**
         * Возвращает массив с постраничной навигацией
         *
         * @param $totalItems
         * @param int $currentPage
         * @param int $itemsPerPage
         * @param int $neighbours
         *
         * @return array
         */
        public function getPagination($totalItems, $currentPage = 1, $itemsPerPage = 10, $neighbours = 2) {
            $pagination = new Pagination($totalItems, $currentPage, $itemsPerPage, $neighbours);

            return $pagination->build();
        }

        public function getParentUri() {
            $uri = '';
            if ($parent = get_parent_class($this)) {
                $parent = explode('\\', $parent);
                $uri = strtolower(array_pop($parent));
            }
            return $uri;
        }

        /**
         * Редирект на указанный адрес
         *
         * @param string $url
         */
        public function redirect($url = '') {
            // redirect parent
            if ($url === 'parent') {
                $url = $this->getParentUri();
            }
//            if (strpos($url, '/') !== 0) {
//                $url = '/' . $url;
//            }
            if ($this->isAjax) {
                $this->core->ajaxResponse(false, 'Редирект на другой адрес', array('redirect' => $url));
            } else {
                header("Location: ".$this->core->baseUrl.$url);
                exit();
            }
        }

        /**
         * Генерирует Url
         * @param string $uri
         * @param array $params Массив параметров для http_buid_query
         * @param $scheme Если full, то полный урл (БЕЗ ГОРОДА)
         * @return string
         */
        public function makeUrl($uri = '', array $params = [], $scheme = -1) {
            // redirect parent
            if ($uri === 'parent') {
                $uri = $this->getParentUri();
            }
            if (!empty($params)) {
                $uri .= '?'.http_build_query($params);
            }
            if ($scheme === 'full') {
                $uri = $this->core->siteUrl.'/'.$uri;
            }
            return $uri;
        }

        public function makePageUrl($page) {
            if ($page != 1) { $_REQUEST['page'] = $page; }
            else { unset($_REQUEST['page']); }
            return $this->makeUrl($this->uri, $_REQUEST);
        }

        public function makeSortUrl() {
            $sortdir = $this->sortdir == 'ASC' ? 'desc' : 'asc';
            return $this->uri.'?'.http_build_query(array_merge($_REQUEST, ['sortdir' => $sortdir]));
        }

        /**
         * Обертка для стандартного метода пользователя Brevis\Model\User
         */
        public function checkPermissions($perms) {
            if ($this->core->authUser) {
                return $this->core->authUser->checkPermissions($perms);
            }
            return false;
        }

        public function loadLexicon($name) {
            $filename = PROJECT_CORE_PATH.'lexicon/ru/'.$name.'.inc.php';
            if (file_exists($filename)) {
                include $filename;
                $results = $_lang;
            } else {
                return [];
            }
            return $results;
        }

        /**
         * Хелпер для форматирования кода года выпуска
         * @param string $value
         * @return string
         */
        public function formatYearKey($value) {
           return sprintf("%02d", $value);
        }

        /**
         * Читает, проверяет и устанавливает фильтры и сортировку для табличных данных
         * @var array $request
         */
        protected function _readFilters($request) {
            foreach ($this->allowedFilters as $filter) {
                // нельзя использовать !empty, т.к., например published = 0
                if (isset($request[$filter]) and strlen($request[$filter]) != 0) {
                    $this->filters[$filter] = $this->core->cleanInput($request[$filter]);
                }
            }
            if (!empty($request['page'])) {
                $page = intval($request['page']);
                $this->page = $page;
                $this->filters['page'] = $this->page;
            }
            $path = !empty($this->core->cityKey) ? '/'.$this->core->cityKey : '';
            $path .= '/'.$this->uri;
            if (!empty($_COOKIE['sortby']) and in_array($_COOKIE['sortby'], $this->allowedSort)) {
                $this->sortby = $_COOKIE['sortby'];
            } else {
                $this->sortby = $this->defaultSort;
            }
            if (!empty($request['sortby']) and in_array($request['sortby'], $this->allowedSort)) {
                $this->sortby = $request['sortby'];
                setcookie('sortby', $this->sortby, 0, $path);
            }
            if (!empty($_COOKIE['sortdir']) and strtoupper($_COOKIE['sortdir']) == 'DESC') {
                $this->sortdir = 'DESC';
            }
            if (!empty($request['sortdir'])) {
                $this->sortdir = strtoupper($request['sortdir']) == 'DESC' ? 'DESC' : 'ASC';
                setcookie('sortdir', $this->sortdir, 0, $path);
            }
            unset($_REQUEST['sortby'],$_REQUEST['sortdir']);
            // устанавливаем фильтры для экспорта
            $this->exportFilters = array_merge($this->filters, ['sortby' => $this->sortby, 'sortdir' => $this->sortdir]);
        }

        /**
         * Список регионов для селекта
         * @param int $country_id Страна
         * @return array
         */
        public function getRegions($country_id = 1) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\Region');
            $c->select('id,name');
            $c->sortby('name');
            if ($c->prepare() and $c->stmt->execute()) {
                return $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            } else {
                $this->core->logger->error('Не могу выбрать Region:' . print_r($c->stmt->errorInfo(), true));
            }
        }

        /**
         * Устанавливает seo-элементы на странице
         * (title, description, keywords
         * @param array $data Плейсхолдеры для шаблона
         */
        public function setSEO(array $data = []) {
            $items = ['pagetitle', 'description', 'keywords'];

            $className = get_class($this);
            $className = explode('\\', $className);
            $className = array_pop($className);
            $city = $this->core->cityKey ?: 'common';

            foreach ($items as $item) {
                $tplName = implode('/', [$item, $className, $city]);
                $this->$item = $this->template($tplName, $data);
            }
        }

    }