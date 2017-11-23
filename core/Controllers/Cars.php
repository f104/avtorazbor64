<?php

    namespace Brevis\Controllers;

    use Brevis\Controller as Controller;

    class Cars extends Controller {

        public $template = 'cars';
        public $langTopic = 'order, cars';
        public $marks = null,
            $models = null, // индексируем так: model_key.year_key, иначе затрутся разные year_key, при сабмите формы с селектами делим на ключи
            $categories = null,
            $elements = null;
        public $mark = null, $model = null, $category = null, $element = null;
        // breadcrumbs
        public $bk = array(), $bkHome = 'Все производители';
        public $selects = array();
//    public $showUnpublished = false; // показывать неопубликованные позиции
        // общие условия для выборки Items
        public $where = [
            'Item.published' => 1,
            'Item.moderate' => 1,
            'Item.error:IS' => null,
        ];

        /**
         *
         * @var bool Показывать цену или нет
         */
        public $showPrice = true;
        public $canBuy = false; // может покупать или нет

        /* @var Brevis/Model/Supplier Авторизованный пользователь - поставщик, нужно, чтобы показывать ему цены на "витрине" */
        public $supplier = null;
        /* @var array Массив с id складов поставщика, нужно, чтобы показывать ему цены на "витрине" только на своих складах */
        public $supplierSklads = null;
        private $_sklads; // незалоченные склады
        public $limit = PROJECT_DEFAULT_QUERY_LIMIT;
        public $page = 1;
        public $total = 0;
        private $_offset = 0;
        public $allowedSort = ['price'];
        public $defaultSort = 'price';
        public $sortdir = 'ASC';

        public function __construct(\Brevis\Core $core) {
            parent::__construct($core);
            $this->pagetitle = $this->lang['pagetitle'];
            $this->description = $this->lang['description'];
            $this->name = $this->lang['name'];
            $this->_sklads = $this->core->getSklads();
            if (!empty($this->_sklads)) {
                $this->where['Item.sklad_id:IN'] = $this->_sklads;
            } else {
                // заведомо ложное условие, все склады выключены или поставщики заблокированы
                $this->where['Item.id'] = 0;
            }
            // цены показывается только авторизованным покупателям
//        if ($this->core->isAuth and $this->core->authUser->checkPermissions('buy')) {
//            $this->showPrice = true;
//        }
            // покупать могут только авторизованные покупатели
            if ($this->core->isAuth and $this->core->authUser->checkPermissions('buy')) {
                $this->canBuy = true;
            }
            $this->_readFilters($_GET);
        }

        /**
         * @return string
         */
        public function run() {
            // marks
            $this->marks = $this->getMarks();
            if (isset($_REQUEST['mark'])) {
                // models
                $this->mark = $this->getMark($_REQUEST['mark']);
                $this->models = $this->getModels($this->mark['mark_key']);
//            var_dump($this->models);
                $bk[] = array(
                    'uri' => $this->uri,
                    'title' => $this->bkHome,
                    'active' => false,
                );
                $this->marks[$this->mark['mark_key']]['selected'] = 'selected';
                $this->selects['mark'] = $this->marks;
                // проверяем сабмит формы и делим model
                if (isset($_REQUEST['model']) and strlen($_REQUEST['model']) == 5 and ! isset($_REQUEST['year'])) {
                    $_REQUEST['year'] = substr($_REQUEST['model'], -2);
                    $_REQUEST['model'] = substr($_REQUEST['model'], 0, 3);
                }
                if (isset($_REQUEST['model']) and isset($_REQUEST['year'])) {
                    // categories
                    $this->model = $this->getModel($this->mark['mark_key'], $_REQUEST['model'], $_REQUEST['year']);
                    $this->categories = $this->getCategories($this->model['mark_key'], $this->model['model_key'], $this->model['year_key']);
                    $bk[] = array(
                        'uri' => $this->uri . '?' . http_build_query(array(
                            'mark' => $this->model['mark_key']
                        )),
                        'title' => $this->model['mark_name'],
                        'active' => false,
                    );
                    $this->models[$this->model['model_key'] . $this->model['year_key']]['selected'] = 'selected';
                    $this->selects['model'] = $this->models;
                    if (isset($_REQUEST['category'])) {
                        // elements
                        $this->category = $this->getCategory($_REQUEST['category']);
                        $this->elements = $this->getElements($this->model['mark_key'], $this->model['model_key'], $this->model['year_key'], $this->category['key']);
                        $bk[] = array(
                            'uri' => $this->uri . '?' . http_build_query(array(
                                'mark' => $this->model['mark_key'],
                                'model' => $this->model['model_key'],
                                'year' => $this->model['year_key'],
                            )),
                            'title' => $this->model['name'],
                            'active' => false,
                        );
                        $this->categories[$this->category['key']]['selected'] = 'selected';
                        $this->selects['category'] = $this->categories;
                        if (isset($_REQUEST['element'])) {
                            // items
                            // подключим поставщика, чтобы показать ему цену
                            if ($this->core->isAuth and $this->supplier = $this->core->authUser->getOne('UserSupplier')) {
                                $c = $this->core->xpdo->newQuery('Brevis\Model\Sklad', ['supplier_id' => $this->supplier->id]);
                                $c->select('id');
                                if ($c->prepare() && $c->stmt->execute()) {
                                    $this->supplierSklads = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
                                }
                            }
                            $this->element = $this->getElement($_REQUEST['element']);
                            $bk[] = array(
                                'uri' => $this->uri . '?' . http_build_query(array(
                                    'mark' => $this->model['mark_key'],
                                    'model' => $this->model['model_key'],
                                    'year' => $this->model['year_key'],
                                    'category' => $this->category['key'],
                                )),
                                'title' => $this->category['name'],
                                'active' => false,
                            );
                            $bk[] = array(
                                'uri' => null,
                                'title' => $this->element['name'],
                                'active' => true,
                            );
                            $this->elements[$this->element['key']]['selected'] = 'selected';
                            $this->selects['element'] = $this->elements;
                            $show = 'items';
                            $this->name = $this->element['name'] . ' для автомобилей ' . $this->mark['mark_name'] . ' ' . $this->model['name'] . ' в Саратове';
                            $this->setSEO([
                                'name' => $this->element['name'] . ' БУ в Саратове',
                                'mark_name' => $this->mark['mark_name'],
                                'model_name' => $this->model['model_name'],
                                'year_name' => $this->model['year_name'],
                            ]);
                            $items = $this->getItems($_REQUEST);
                            if (empty($items)) {
                                // redirect to "elements"
                                $this->redirectUp(array(
                                    'mark' => $this->model['mark_key'],
                                    'model' => $this->model['model_key'],
                                    'year' => $this->model['year_key'],
                                    'category' => $this->category['key'],
                                ));
                            }
                        } else {
                            $show = 'elements';
                            $this->name = 'Запчасти (' . mb_strtolower($this->category['name']) . ') БУ для автомобилей ' . $this->mark['mark_name'] . ' ' . $this->model['name'] . ' в Саратове';
                            $this->setSEO([
                                'name' => 'Запчасти (' . mb_strtolower($this->category['name']) . ') БУ в Саратове',
                                'mark_name' => $this->mark['mark_name'],
                                'model_name' => $this->model['model_name'],
                                'year_name' => $this->model['year_name'],
                            ]);
                            $items = $this->elements;
                            $bk[] = array(
                                'uri' => null,
                                'title' => $this->category['name'],
                                'active' => true,
                            );
                        }
                    } else {
                        $show = 'categories';
                        $this->name = 'Запчасти БУ для автомобилей ' . $this->mark['mark_name'] . ' ' . $this->model['name'] . ' в Саратове';
                        $this->setSEO([
                            'name' => 'Запчасти БУ в Саратове',
                            'mark_name' => $this->mark['mark_name'],
                            'model_name' => $this->model['model_name'],
                            'year_name' => $this->model['year_name'],
                        ]);
                        $items = $this->categories;
                        $bk[] = array(
                            'uri' => null,
                            'title' => $this->model['name'],
                            'active' => true,
                        );
                    }
                } else {
                    $show = 'models';
                    $this->name = 'Запчасти БУ для автомобилей ' . $this->mark['mark_name'] . ' в Саратове';
                    $this->setSEO([
                        'name' => 'Запчасти БУ в Саратове',
                        'mark_name' => $this->mark['mark_name'],
                    ]);
                    $items = $this->models;
                    $bk[] = array(
                        'uri' => null, //$this->uri.'?'.http_build_query(array('mark' => $this->mark['mark_key'])),
                        'title' => $this->mark['mark_name'],
                        'active' => true,
                    );
                }
            } else {
                $show = 'marks';
                $items = $this->marks;
                $bk[] = array(
                    'uri' => $this->uri,
                    'title' => $this->bkHome,
                    'active' => true,
                );
            }
            $data = array(
                'show' => $show,
                'uri' => $this->uri,
                'items' => $items,
                'marks' => $this->marks,
                'mark' => $this->mark,
                'models' => $this->models,
                'model' => $this->model,
                'breadcrumbs' => $this->template('_breadcrumbs', array('items' => $bk), $this),
                'selects' => $this->template('_selects', array('home' => $this->uri, 'selects' => $this->selects), $this),
                'content' => '',
                'showPrice' => $this->showPrice,
                'canBuy' => $this->canBuy,
                'totalItems' => $this->getTotalItems(),
//            'total' => $this->_total,
//            'offset' => $this->_offset,
                'pagination' => $this->getPagination($this->total, $this->page, $this->limit),
            );
//        print_r($this->isAjax, true);
//        die;
            if ($this->isAjax) {
                $this->core->ajaxResponse(true, '', array(
                    'title' => $data['title'],
                    'content' => $this->template('_cars.content', $data, $this),
                ));
            } else {
                return $this->template($this->template, $data, $this);
            }
        }

        /**
         * get "mark"
         * @param string $mark_key
         * @return array||redirect
         */
        public function getMark($mark_key) {
            if (array_key_exists($mark_key, $this->marks)) {
                return $this->marks[$mark_key];
            } else {
                $this->redirectUp();
            }
        }

        /**
         * get "model"
         * @param string $mark_key
         * @param string $model_key
         * @param string $year_key
         * @return array||redirect
         */
        public function getModel($mark_key, $model_key, $year_key = null) {
            if ($mark_key == $this->mark['mark_key'] and array_key_exists($model_key . $year_key, $this->models)) {
                return $this->models[$model_key . $year_key];
            } else {
                $this->redirectUp(array('mark' => $this->mark['mark_key']));
            }
        }

        /**
         * get "category"
         * @param string $key
         * @return array||redirect
         */
        public function getCategory($key) {
            if (array_key_exists($key, $this->categories)) {
                return $this->categories[$key];
            } else {
                $this->redirectUp(array(
                    'mark' => $this->model['mark_key'],
                    'model' => $this->model['model_key'],
                    'year' => $this->model['year_key'],
                ));
            }
        }

        /**
         * get element
         * @param string $key
         * @return array||redirect
         */
        public function getElement($key) {
            if (array_key_exists($key, $this->elements)) {
                return $this->elements[$key];
            } else {
                $this->redirectUp(array(
                    'mark' => $this->model['mark_key'],
                    'model' => $this->model['model_key'],
                    'year' => $this->model['year_key'],
                    'category' => $this->category['key'],
                ));
            }
        }

        /**
         * Общее кол-во деталей
         *
         * @return array
         */
        public function getTotalItems() {
            if (!$total = $this->core->cacheManager->get('items.total')) {
                $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
                $c->where($this->where);
                $total = $this->core->xpdo->getCount('Brevis\Model\Item', $c);
                $this->core->cacheManager->add('items.total', $rows, $this->core->cacheLifetime);
            }
            return number_format($total, 0, ".", " ");
        }

        /**
         * Выборка марок авто
         *
         * @return array
         */
        public function getMarks() {
            if (!$rows = $this->core->cacheManager->get('cars.marks')) {
                $rows = array();
                $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
                $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Cars', 'Cars', '', array('mark_key','mark_name')));
                $c->distinct();
                $c->sortby('mark_name', 'ASC');
//                $c->innerJoin('Brevis\Model\Item', 'Item', 'Item.mark_key=Cars.mark_key');
//                $where = $this->where;
//                $where['mark_key:!='] = '';
//                $where['mark_name:!='] = '';
//                $c->where($where);
                if ($c->prepare() && $c->stmt->execute()) {
                    $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
                    foreach ($rows as $k => $row) {
                        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
                        $where = $this->where;
                        $where['mark_key'] = $row['mark_key'];
                        $c->where($where);
                        $count = $this->core->xpdo->getCount('Brevis\Model\Item', $c);
                        if ($count == 0) {
                            unset ($rows[$k]);
                        }
                    }
                } else {
                    $this->core->logger->error('Не могу выбрать marks:' . print_r($c->stmt->errorInfo(), true));
                }
                $this->core->cacheManager->add('cars.marks', $rows, $this->core->cacheLifetime);
            }
            $marks = array();
            foreach ($rows as $row) {
                $row['uri'] = $this->uri.'?mark='.$row['mark_key'];
                $row['name'] = $row['mark_name'];
                $marks[$row['mark_key']] = $row;
            }
            return $marks;
        }

        /**
         * Выборка моделей авто
         * @param string mark_key
         * @return array||redirect
         */
        public function getModels($mark_key) {
            $rows = array();
            $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Cars', 'Cars'));
            $c->distinct();
            $c->sortby('model_name', 'ASC');
            $c->innerJoin('Brevis\Model\Item', 'Item', 'Item.model_key=Cars.model_key');
            $where = array('mark_key' => $mark_key, 'Item.mark_key' => $mark_key);
            $where[] = "Item.year_key = LPAD(Cars.year_key,2,'0')"; //FUCK!
            $where = array_merge($where, $this->where);
            $c->where($where);
//        $where['Item.year_key'] = empty($year_key) ? '' :  sprintf("%'.02d", $year_key);
            if ($c->prepare() && $c->stmt->execute()) {
//            var_dump($c->toSQL()); die;
                $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $this->core->log('Не могу выбрать models:' . print_r($c->stmt->errorInfo(), true));
            }
            $models = array();
            foreach ($rows as $row) {
                $row['year_key'] = sprintf("%'.02d", $row['year_key']);
                $row['uri'] = $this->uri . '?' . http_build_query(array(
                        'mark' => $row['mark_key'],
                        'model' => $row['model_key'],
                        'year' => $row['year_key'],
                ));
                $row['name'] = $row['year_name'] ? : $row['model_name'];
                $models[$row['model_key'] . $row['year_key']] = $row;
            }
            if ($models) {
                return $models;
            } else {
                $this->redirectUp(); // up to marks
            }
        }

        /**
         * Выборка категорий
         * 
         * @param string $mark_key
         * @param string $model_key
         * @param string $year_key
         * 
         * @return array||redirect
         */
        public function getCategories($mark_key, $model_key, $year_key) {
            $rows = array();
            $c = $this->core->xpdo->newQuery('Brevis\Model\Category');
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Category', 'Category'));
//        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Item', 'Item', '', array('mark_key','model_key','year_key')));
            $c->distinct();
            $c->sortby('name', 'ASC');
            $c->innerJoin('Brevis\Model\Item', 'Item', 'Item.category_key=Category.key');
            $where = array('Item.mark_key' => $mark_key, 'Item.model_key' => $model_key);
            $where['Item.year_key'] = empty($year_key) ? '' : $this->formatYearKey($year_key);
            $where = array_merge($where, $this->where);
            $c->where($where);
//        $c->prepare(); var_dump($c->toSQL()); exit;
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $this->core->log('Не могу выбрать category:' . print_r($c->stmt->errorInfo(), true));
            }
            $categories = array();
            foreach ($rows as $row) {
                $row['uri'] = $this->uri . '?' . http_build_query(array(
                        'mark' => $mark_key,
                        'model' => $model_key,
                        'year' => $year_key,
                        'category' => $row['key'],
                ));
                $categories[$row['key']] = $row;
            }
            if ($categories) {
                return $categories;
            } else {
                $this->redirectUp(array(
                    'mark' => $this->model['mark_key'],
                    'model' => $this->model['model_key'],
                    'year' => $this->model['year_key'],
                ));
            }
        }

        /**
         * Выборка элементов
         *
         * @params string $mark_key, $model_key, $year_key, $category_key
         * @return array||redirect
         */
        public function getElements($mark_key, $model_key, $year_key, $category_key) {
            $rows = array();
            $c = $this->core->xpdo->newQuery('Brevis\Model\Element');
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Element', 'Element'));
            $c->distinct();
            $c->sortby('name', 'ASC');
            $c->innerJoin('Brevis\Model\Item', 'Item', 'Item.element_key=Element.key');
            $where = array(
                'Item.mark_key' => $mark_key,
                'Item.model_key' => $model_key,
                'Item.category_key' => $category_key,
            );
            $where['Item.year_key'] = empty($year_key) ? '' : $this->formatYearKey($year_key);
            $where = array_merge($where, $this->where);
            $c->where($where);
//        $c->prepare(); var_dump($c->toSQL()); exit;
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $this->core->log('Не могу выбрать element:' . print_r($c->stmt->errorInfo(), true));
            }
            $elements = array();
            foreach ($rows as $row) {
                $row['uri'] = $this->uri . '?' . http_build_query(array(
                        'mark' => $mark_key,
                        'model' => $model_key,
                        'year' => $year_key,
                        'category' => $category_key,
                        'element' => $row['key'],
                ));
                $elements[$row['key']] = $row;
            }
            if ($elements) {
                return $elements;
            } else {
                $this->redirectUp(array(
                    'mark' => $this->model['mark_key'],
                    'model' => $this->model['model_key'],
                    'year' => $this->model['year_key'],
                    'category' => $this->category['key'],
                ));
            }
        }

        /**
         * Для покупателей подключаются заказы 
         * @param XPDO query $c
         * @return XPDO query
         */
        private function _joinBuyerOrders($c) {

            return $c;
        }

        /**
         * выборка items
         * @param request $param
         * @return array
         */
        public function getItems($param) {
            $rows = array();
            $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Item', 'Item'));
            $c->sortby($this->sortby, $this->sortdir);
            $where = $this->where;
            if (!empty($param['mark'])) {
                $where['mark_key'] = $param['mark'];
            }
            if (!empty($param['model'])) {
                $where['model_key'] = $param['model'];
            }
            if (!empty($param['year'])) {
                $where['year_key'] = $this->formatYearKey($param['year']);
            }
            if (!empty($param['category'])) {
                $where['category_key'] = $param['category'];
            }
            if (!empty($param['element'])) {
                $where['element_key'] = $param['element'];
            }
            $c->where($where);
            $this->total = $this->core->xpdo->getCount('Brevis\Model\Item', $c);
            if ($this->page < 1 or $this->page > ceil($this->total / $this->limit)) {
                $this->page = 1;
            }
            $this->_offset = $this->limit * ($this->page - 1);
            $c->limit($this->limit, $this->_offset);
            $c->leftJoin('Brevis\Model\BodyType', 'BodyType');
            $c->select('BodyType.name AS bodytype_name');
            if ($this->showPrice) {
                $c->leftJoin('Brevis\Model\Sklad', 'Sklad');
                $c->select('Sklad.region_id');
                $c->leftJoin('Brevis\Model\Country', 'Country', ('Country.id = Sklad.country_id'));
                $c->select('Country.iso AS country_iso');
            }
//        $c->prepare(); var_dump($c->toSQL()); exit;
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);

                if (!empty($rows)) {

                    // индексируем массив по id
                    $rowsIndexed = $idx = array();
                    $ids = array(); // массив для выборки фотографий
                    foreach ($rows as $item) {
                        if ($this->showPrice) {
                            // т.к. уже есть элементы, то сэкономим один leftJoin
                            $item['increase_category'] = $this->getElement($item['element_key'])['increase_category'];
                            $this->core->calculatePrice($item, $this->core->authUser);
//                        $item['order_exist'] = $item['order_user_id'] == $this->core->authUser->id;
                        }
                        $ids[] = $item['id'];
                        $rowsIndexed[$item['id']] = $item;
                    }
                    $rows = $rowsIndexed;

                    // images
                    $images = array();
                    $c = $this->core->xpdo->newQuery('Brevis\Model\ItemImages');
                    $c->where(['item_id:IN' => $ids]);
                    $c->where(['filename:IS NOT' => null, 'OR:url:IS NOT' => null]);
                    $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\ItemImages', 'ItemImages', '', array('id', 'item_key', 'item_id', 'filename', 'url', 'prefix')));
                    $c->sortby('`order`');
//                $c->prepare(); var_dump($c->toSQL()); exit;
                    if ($c->prepare() && $c->stmt->execute()) {
                        $images = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
                        if (!empty($images)) {
                            $idx = 0;
                            foreach ($images as $item) {
                                $rows[$item['item_id']]['images'][] = $item;
                                $idx++;
                            }
                        }
                    } else {
                        $this->core->log('Не могу выбрать ItemImages:' . print_r($c->stmt->errorInfo(), true));
                    }
                }
            } else {
                $this->core->log('Не могу выбрать Item:' . print_r($c->stmt->errorInfo(), true));
            }

            return $rows;
        }

        /**
         * Формирует код детали
         * 
         * AEB1A014A0200001
         * 2 символа – марка
         * 3 символа – модель
         * 2 символа – года выпуска
         * 1 символ  – категория
         * 4 символа – список деталей
         * 4 символа – счетчик
         * 
         * @param array $item
         * @return string
         */
        private function createCode($item) {
            $code = $item['mark_key'];
            $code .= $item['model_key'];
            $code .= $this->formatYearKey($item['year_key']);
            $code .= $item['category_key'];
            $code .= $item['element_key'];
            $code .= $item['counter'];
            return $code;
        }

        /**
         * Хелпер для организации редиректа
         * 
         * @param array $param Массив для http_build_query()
         * @return redirect
         */
        private function redirectUp(array $param = array()) {
            $this->redirect($this->url . '?' . http_build_query($param));
        }

    }

    class Buy extends Cars {

        public $permissions = ['buy'];

        public function run() {
            if (!empty($_REQUEST['id'])) {
//             and $item = $this->core->xpdo->getObject('Brevis\Model\Item', $this->where)) and $sklad = $item->getOne('Sklad')
                $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
                $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Item', 'Item'));
                $c->leftJoin('Brevis\Model\Sklad', 'Sklad');
                $c->select('Sklad.city_id, Sklad.region_id');
                $c->leftJoin('Brevis\Model\Element', 'Element', 'Element.key = Item.element_key');
                $c->select('Element.increase_category');
                $c->where(array_merge(['Item.id' => $_REQUEST['id']], $this->where));
//            $c->prepare(); die($c->toSQL());
                if ($c->prepare() and $c->stmt->execute()) {
                    $item = $c->stmt->fetch(\PDO::FETCH_ASSOC);
//                var_dump($item); die;
                    if ($item) {
                        if (!empty($item['reserved'])) {
                            $this->message = $this->lang['order.order_exist'];
                        } else {
                            $order = $this->core->xpdo->newObject('Brevis\Model\Order', [
                                'item_id' => $item['id'],
                                'item_name' => $item['name'],
                                'item_price' => $item['price'],
                                'item_code' => $item['code'],
                                'user_id' => $this->core->authUser->id,
                                'user_city_id' => $this->core->authUser->city_id,
                                'sklad_id' => $item['sklad_id'],
                                'sklad_prefix' => $item['prefix'],
                                'sklad_city_id' => $item['city_id'],
                                'createdon' => date('Y-m-d H:i:s', time()),
                                'cost' => $this->core->calculatePrice($item, $this->core->authUser),
                                'remote_id' => $item['remote_id'],
                                'comment' => !empty($_REQUEST['comment']) ? $this->core->cleanInput($_REQUEST['comment']) : null,
                            ]);
                            if ($order->save()) {
                                $this->_sendEmail($order);
                                // ставим товар в резерв
                                $itemObject = $this->core->xpdo->getObject('Brevis\Model\Item', $item['id']);
                                $itemObject->set('reserved', 1);
                                $itemObject->save();
                                $this->message = $this->lang['order.order_processed'];
                                $this->redirect($this->makeUrl('orders'));
                            } else {
                                $this->message = 'Order processing error.';
                            }
                        }
                    } else {
                        $this->message = $this->lang['order.order_item_notfound'];
                    }
                } else {
                    $this->message = $this->lang['order.order_item_notfound'];
                }
            } else {
                $this->message = $this->lang['order.order_item_notfound'];
            }
            $this->success = true;
            if ($this->isAjax) {
                $this->core->ajaxResponse($this->success, $this->message);
            } else {
                return $this->message;
            }
        }

        /**
         * Отправляет уведомление поставщику
         * @param \Brevis\Model\Order $order
         * @return void
         */
        private function _sendEmail(\Brevis\Model\Order $order) {
            $emailUser = $this->core->xpdo->getObjectGraph('Brevis\Model\Sklad', ['Supplier' => ['User']], $order->sklad_id);

            $additional_emails = 'avgmsk@inbox.ru,' . $emailUser->additional_emails;

            $emailUser = $emailUser->Supplier->User;
            $content = 'У вас новый заказ #' . $order->id . ' (' . $order->item_name . ').';

            $processor = $this->core->runProcessor('Mail\Send', [
                'toName' => $emailUser->name,
                'toMail' => $emailUser->email,
                'cc' => $additional_emails,
                'subject' => 'Новый заказ',
                'body' => $this->template('_mail', [
                    'name' => $emailUser->name,
                    'content' => $content,
                    ], $this),
            ]);

            if (!$processor->isSuccess()) {
                $this->core->logger->error('Не удалось отправить письмо о новом заказе ' . $order->id);
            }
        }

    }
    