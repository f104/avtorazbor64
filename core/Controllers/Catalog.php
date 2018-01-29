<?php

/**
 * Управление товарами
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Catalog extends Controller {

    public $name = 'Каталог';
    public $permissions = ['catalog_view'];
    public $langTopic = 'catalog';
    public $template = 'catalog';
    public $classname = 'Brevis\Model\Cars';
    
    /** @var Brevis\Components\AvangardCode */
    public $avCode;

    public $additionalJs = ['assets/js/hogan-3.0.1.js', 'assets/js/catalog.js'];

    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->avCode = new \Brevis\Components\AvangardCode\AvangardCode($this->core);
    }

    /** 
     * Выборка марок
     * @return array
     */
    public function getMarks() {
        $items = [];
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Cars'));
        $c->sortby('mark_name');
        $c->groupby('mark_key');
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname . ':' . print_r($c->stmt->errorInfo(), true));
        }
        // add aliases
        $markAliases = $this->getMarkAliases();
        foreach ($items as &$item) {
            if (isset($markAliases[$item['mark_key']])) {
                $item['alias'] = implode(', ', $markAliases[$item['mark_key']]);
            } else {
                $item['alias'] = '';
            }
        }
        return $items;
    }
    
    /** 
     * Выборка aliases марок
     * @return array
     */
    public function getMarkAliases() {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\MarkAlias');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\MarkAlias', 'MarkAlias'));
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $items[$row['mark_key']][] = $row['alias'];
            }
        } else {
            $this->core->log('Не могу выбрать Brevis\Model\MarkAlias:' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    
    
    /** 
     * Выборка моделей
     * @param string $mark_key
     * @return array
     */
    public function getModels($mark_key) {
        $items = [];
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->where(['mark_key:=' => $mark_key]);
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Cars'));
        $c->where([
            'year_key:!=' => '',
        ]);
        $c->sortby('model_name');
        $c->groupby('model_key');
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname . ':' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /**
     * Выборка поколений
     * @param string $mark_key
     * @param string $model_key
     * @return array
     */
    public function getYears($mark_key, $model_key) {
        $items = [];
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->where([
            'mark_key:=' => $mark_key, 
            'model_key:=' => $model_key,
            'year_key:!=' => '',
        ]);
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Cars'));
        $c->sortby('year_name');
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname . ':' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /** 
     * Выборка категорий
     * @return array
     */
    public function getCategories() {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\Category');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Category', 'Category'));
        $c->sortby('name');
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать Brevis\Model\Category:' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /** 
     * Выборка элементов
     * @param string $category_key
     * @return array
     */
    public function getElements($category_key) {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\Element');
        $c->where(['category_key:=' => $category_key]);
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Element', 'Element'));
        $c->sortby('name');
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать Brevis\Model\Element:' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /** 
     * Выборка наценок
     * @return array
     */
    public function getIncreases() {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\Increase');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Increase', 'Increase'));
        $c->sortby('id');
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать Brevis\Model\Increase:' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /** 
     * Выборка типов кузова
     * @return array
     */
    public function getBodytypes() {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\BodyType');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\BodyType', 'BodyType'));
        $c->sortby('name');
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать Brevis\Model\BodyType:' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    public function getSelectYears() {
        $years = [];
        for($i = (int)date('Y'); $i >= 1900; $i--) {
            $years[] = $i;
        }
        return $years;
    }


    public function run() {
//        // тестирование ключей 
//        if ($key = $this->avCode->getNextElementKey('BJ', 'A1F')) {
//            var_dump($key);
//        } else {
//            var_dump($this->avCode->getError());
//        }
        $marks = $this->getMarks();
        $categories = $this->getCategories();
        $models = $years = $elements = $increas = [];
        $selectYears = $this->getSelectYears();
        $increases = $this->getIncreases();
        $bodytypes = $this->getBodytypes();
        if (!empty($_REQUEST['mark_key'])) {
            $models = $this->getModels($_REQUEST['mark_key']);
            if (!empty($_REQUEST['model_key'])) {
                $years = $this->getYears($_REQUEST['mark_key'], $_REQUEST['model_key']);
            }
        }
        if (!empty($_REQUEST['category_key'])) {
            $elements = $this->getElements($_REQUEST['category_key']);
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', [
                    'mark' => json_encode($marks),
                    'model' => json_encode($models),
                    'year' => json_encode($years),
                    'selectYears' => json_encode($selectYears),
                    'category' => json_encode($categories),
                    'element' => json_encode($elements),
                    'increases' => json_encode($increases),
                ]);
        }
        return $this->template($this->template, [
            'mark' => $marks,
            'model' => $models,
            'year' => $years,
            'selectYears' => $selectYears,
            'category' => $categories,
            'element' => $elements,
            'increases' => $increases,
            'bodytypes' => $bodytypes,
        ], $this);
    }
    
}

/**
 * Обработка запроса на добавление/редактирование/удаление марки
 */
class UpdateMark extends Catalog {
    public function run() {
        $errors = [];
        $this->message = $this->lang['form_error_msg'];
        if (empty($_REQUEST['name'])) {
            $errors['name'] = $this->lang['catalog.name_error_nf'];
        } else {
            $name = $this->core->cleanInput($_REQUEST['name']);
            if (empty($name)) {
                $errors['name'] = $this->lang['catalog.name_error_nf'];
            } else {
                if (empty($_REQUEST['key'])) {
                    // new
                    if ($this->_alreadyExist($name)) {
                        $errors['name'] = $this->lang['catalog.name_error_ae'];
                    } else {
                        if ($mark_key = $this->avCode->getNextMarkKey()) {
                            $car = $this->core->xpdo->newObject($this->classname, [
                                'mark_key' => $mark_key,
                                'mark_name' => mb_strtoupper($name),
                                'model_key' => $model_key = $this->avCode->getNextModelKey($mark_key),
                                'model_name' => '[переименовать]',
                                'year_key' => $this->avCode->getNextYearKey($mark_key, $model_key),
                                'year_name' => '[переименовать]',
                            ]);
                            if ($car->save()) {
                                $this->success = true;
                                $this->message = $this->lang['saved'];
                                $this->_updateAliases($mark_key, $_REQUEST['alias']);
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        } else {
                            $errors['name'] = $this->avCode->getError();
                        }
                    }
                } else {
                    $mark_key = $_REQUEST['key'];
                    if (!isset($_REQUEST['remove'])) {
                        // update
                        if ($this->_alreadyExist($name, $mark_key)) {
                            $errors['name'] = $this->lang['catalog.name_error_ae'];
                        } else {
    //                        $cars = $this->core->xpdo->updateCollection($this->classname, 
    //                            ['mark_name' => mb_strtoupper($name)],
    //                            ['mark_key' => $mark_key]
    //                        );
    //                        if ($cars !== false) {
    //                            $this->success = true;
    //                            $this->message = $this->lang['saved'];
    //                        } else {
    //                            $this->message = $this->lang['save_db_error'];
    //                            $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
    //                        }
                            $cars = $this->core->xpdo->getCollection($this->classname, 
                                ['mark_key' => $mark_key]
                            );
                            foreach ($cars as $item) {
                                $item->set('mark_name', mb_strtoupper($name));
                                $item->save();
                            }
                            $this->success = true;
                            $this->message = $this->lang['saved'];
                            $this->_updateAliases($mark_key, $_REQUEST['alias']);
                        }
                    } else {
                        // remove
                        if ($items = $this->core->xpdo->getCount('Brevis\Model\Item', [
                            'mark_key' => $mark_key,
                        ])) {
                            $this->message = $this->lang['catalog.name_error_items_exist'];
                        } else {
                            if ($this->core->xpdo->getCount($this->classname, [
                                'mark_key:!=' => $mark_key,
                                'year_name:!=' => '', // TODO empty years
                            ]) == 0) {
                                $this->message = $this->lang['catalog.name_error_remove_last'];
                            } else {
                                if ($this->core->xpdo->removeCollection($this->classname, [
                                    'mark_key' => $mark_key,
                                ])) {
                                    $this->success = true;
                                    $this->message = $this->lang['removed'];
                                    $this->_removeAliases($mark_key);
                                } else {
                                    $this->message = $this->lang['save_db_error'];
                                    $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                                }
                            }
                        }
                    }
                }
            }
        }
        // в случае успеха возвращаем новый список и ключ для подсветки
        $items = $select = '';
        if ($this->success) {
            $items = json_encode($this->getMarks());
            if (!isset($_REQUEST['remove'])) { $select = $mark_key; }
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors, 'items' => $items, 'select' => $select]);
        } else {
            die($this->message);
        }
    }
    
    /**
     * Проверка на дубликат
     * @param string $mark_name
     * @param string $mark_key
     * @return bool
     */
    private function _alreadyExist($mark_name, $mark_key = 0) {
        $c = ['mark_name' => $mark_name];
        if (!empty($mark_key)) {
            $c['mark_key:!='] = $mark_key;
        }
        $ae = $this->core->xpdo->getCount($this->classname, $c);
        return $ae != 0;
    }
    
    private function _removeAliases($mark_key) {
        $this->core->xpdo->removeCollection('Brevis\Model\MarkAlias', ['mark_key' => $mark_key]);
    }
    
    private function _updateAliases($mark_key, $aliases) {
        $this->_removeAliases($mark_key);
        if (!empty($aliases)) {
            $aliases = explode(',', $aliases);
            $aliases = array_map('trim', $aliases);
            foreach ($aliases as $alias) {
                $obj = $this->core->xpdo->newObject('Brevis\Model\MarkAlias', [
                    'mark_key' => $mark_key,
                    'alias' => $alias,
                ]);
                $obj->save();
            }
        }
    }
}

/**
 * Обработка запроса на добавление/редактирование/удаление модели
 */
class UpdateModel extends Catalog {
    public function run() {
        $errors = [];
        $this->message = $this->lang['form_error_msg'];
        if (empty($_REQUEST['name'])) {
            $errors['name'] = $this->lang['catalog.name_error_nf'];
        } else {
            $name = $this->core->cleanInput($_REQUEST['name']);
            if (empty($name)) {
                $errors['name'] = $this->lang['catalog.name_error_nf'];
            }
        }
        if (empty($_REQUEST['mark_key'])) {
            $errors['name'] = $this->lang['catalog.name_error_mark_key_nf'];
        } elseif (!$car = $this->core->xpdo->getObject($this->classname, ['mark_key' => $_REQUEST['mark_key']])) { // get car with this mark_key
            $errors['name'] = $this->lang['catalog.name_error_mark_key_incorrect']; 
        }
        if (empty($errors)) {
            if (empty($_REQUEST['key'])) {
                // new
                if ($this->_alreadyExist($name, $car->mark_key)) {
                    $errors['name'] = $this->lang['catalog.name_error_ae'];
                } else {
                    if ($model_key = $this->avCode->getNextModelKey($car->mark_key)) {
                        $newCar = $this->core->xpdo->newObject($this->classname, [
                            'mark_key' => $car->mark_key,
                            'mark_name' => $car->mark_name,
                            'model_key' => $model_key,
                            'model_name' => $name,
                            'year_key' => $this->avCode->getNextYearKey($car->mark_key, $model_key),
                            'year_name' => '[переименовать]',
                        ]);
                        if ($newCar->save()) {
                            $this->success = true;
                            $this->message = $this->lang['saved'];
                        } else {
                            $this->message = $this->lang['save_db_error'];
                            $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                        }
                    } else {
                        $errors['name'] = $this->avCode->getError();
                    }
                }
            } else {
                $model_key = $_REQUEST['key'];
                if (!isset($_REQUEST['remove'])) {
                    // update
                    if ($this->_alreadyExist($name, $car->mark_key, $model_key)) {
                        $errors['name'] = $this->lang['catalog.name_error_ae'];
                    } else {
    //                    $cars = $this->core->xpdo->updateCollection($this->classname, 
    //                        ['model_name' => $name],
    //                        ['mark_key' => $car->mark_key, 'model_key' => $model_key]
    //                    );
    //                    if ($cars !== false) {
    //                        $this->success = true;
    //                        $this->message = $this->lang['saved'];
    //                    } else {
    //                        $this->message = $this->lang['save_db_error'];
    //                        $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
    //                    }
                        $cars = $this->core->xpdo->getCollection($this->classname, 
                            ['mark_key' => $car->mark_key, 'model_key' => $model_key]
                        );
                        foreach ($cars as $item) {
                            $item->set('model_name', $name);
                            $item->save();
                        }
                        $this->success = true;
                        $this->message = $this->lang['saved'];
                    }
                } else {
                    // remove
                    if ($items = $this->core->xpdo->getCount('Brevis\Model\Item', [
                        'mark_key' => $car->mark_key,
                        'model_key' => $model_key,
                    ])) {
                        $this->message = $this->lang['catalog.name_error_items_exist'];
                    } else {
                        if ($this->core->xpdo->getCount($this->classname, [
                            'mark_key' => $car->mark_key,
                            'model_key:!=' => $model_key,
                            'year_name:!=' => '', // TODO empty years
                        ]) == 0) {
                            $this->message = $this->lang['catalog.name_error_remove_last'];
                        } else {
                            if ($this->core->xpdo->removeCollection($this->classname, [
                                'mark_key' => $car->mark_key,
                                'model_key' => $model_key,
                            ])) {
                                $this->success = true;
                                $this->message = $this->lang['removed'];
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        }
                    }
                }
            }
        }
        // в случае успеха возвращаем новый список и ключ для подсветки
        $items = $select = '';
        if ($this->success) {
            $items = json_encode($this->getModels($car->mark_key));
            if (!isset($_REQUEST['remove'])) { $select = $model_key; }
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors, 'items' => $items, 'select' => $select]);
        } else {
            die($this->message);
        }
    }
    
    /**
     * Проверка на дубликат
     * @param string $model_name
     * @param string $mark_key
     * @param string $model_key
     * @return bool
     */
    private function _alreadyExist($model_name, $mark_key, $model_key = 0) {
        $c = [
            'model_name' => $model_name,
            'mark_key' => $mark_key,
        ];
        if (!empty($model_key)) {
            $c['model_key:!='] = $model_key;
        }
        $ae = $this->core->xpdo->getCount($this->classname, $c);
        return $ae != 0;
    }
}

/**
 * Обработка запроса на добавление/редактирование/удаление поколения
 */
class UpdateYear extends Catalog {
    public function run() {
        $errors = [];
        $this->message = $this->lang['form_error_msg'];
        if (empty($_REQUEST['name'])) {
            $errors['name'] = $this->lang['catalog.name_error_nf'];
        } else {
            $name = $this->core->cleanInput($_REQUEST['name']);
            if (empty($name)) {
                $errors['name'] = $this->lang['catalog.error_nf'];
            }
        }
        if (empty($_REQUEST['year_start'])) {
            $errors['year_start'] = $this->lang['catalog.error_nf'];
        } else {
            $year_start = intval($_REQUEST['year_start']);
            if (empty($year_start)) {
                $errors['year_start'] = $this->lang['catalog.error_nf'];
            } else {
                if (!empty($_REQUEST['year_finish']) and intval($_REQUEST['year_finish']) <= $year_start) {
                    $errors['year_start'] = $this->lang['catalog.error_incorrect'];                    
                }
            }
        }
        if (empty($_REQUEST['mark_key']) or empty($_REQUEST['model_key'])) {
            $errors['name'] = $this->lang['catalog.name_error_mark_key_nf'];
        } elseif (!$car = $this->core->xpdo->getObject($this->classname, [
                'mark_key' => $_REQUEST['mark_key'],
                'model_key' => $_REQUEST['model_key'],
            ])) { // get car with this mark_key
            $errors['name'] = $this->lang['catalog.name_error_mark_key_incorrect']; 
        }
        if (empty($errors)) {
            if (empty($_REQUEST['key'])) {
                // new
                if ($this->_alreadyExist($name, $car->mark_key, $car->model_key)) {
                    $errors['name'] = $this->lang['catalog.name_error_ae'];
                } else {
                    if ($year_key = $this->avCode->getNextYearKey($car->mark_key, $car->model_key)) {
                        $newCar = $this->core->xpdo->newObject($this->classname, [
                            'mark_key' => $car->mark_key,
                            'mark_name' => $car->mark_name,
                            'model_key' => $car->model_key,
                            'model_name' => $car->model_name,
                            'year_key' => $year_key,
                            'year_name' => $name,
                            'year_start' => $_REQUEST['year_start'],
                            'year_finish' => $_REQUEST['year_finish'],
                        ]);
                        if ($newCar->save()) {
                            $this->success = true;
                            $this->message = $this->lang['saved'];
                        } else {
                            $this->message = $this->lang['save_db_error'];
                            $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                        }
                    } else {
                        $errors['name'] = $this->avCode->getError();
                    }
                }
            } else {
                $year_key = $_REQUEST['key'];
                if (!isset($_REQUEST['remove'])) {
                    // update
                    if ($this->_alreadyExist($name, $car->mark_key, $car->model_key, $year_key)) {
                        $errors['name'] = $this->lang['catalog.name_error_ae'];
                    } else {
                        $car = $this->core->xpdo->getObject($this->classname, 
                            ['mark_key' => $car->mark_key, 'model_key' => $car->model_key, 'year_key' => $year_key]
                        );
                        $car->set('year_name', $name);
                        $car->set('year_start', $_REQUEST['year_start']);
                        $car->set('year_finish', $_REQUEST['year_finish']);
                        if ($car->save()) {
                            $this->success = true;
                            $this->message = $this->lang['saved'];
                        } else {
                            $this->message = $this->lang['save_db_error'];
                            $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                        }
                    }
                } else {
                    // remove
                    if ($items = $this->core->xpdo->getCount('Brevis\Model\Item', [
                        'mark_key' => $car->mark_key,
                        'model_key' => $car->model_key,
                        'year_key' => $year_key,
                    ])) {
                        $this->message = $this->lang['catalog.name_error_items_exist'];
                    } else {
                        if ($this->core->xpdo->getCount($this->classname, [
                            'mark_key' => $car->mark_key,
                            'model_key' => $car->model_key,
                            'year_key:!=' => $year_key,
                            'year_name:!=' => '', // TODO empty years
                        ]) == 0) {
                            $this->message = $this->lang['catalog.name_error_remove_last'];
                        } else {
                            if ($this->core->xpdo->removeCollection($this->classname, [
                                'mark_key' => $car->mark_key,
                                'model_key' => $car->model_key,
                                'year_key' => $year_key,
                            ])) {
                                $this->success = true;
                                $this->message = $this->lang['removed'];
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        }
                    }
                }
            }
        }
        // в случае успеха возвращаем новый список и ключ для подсветки
        $items = $select = '';
        if ($this->success) {
            $items = json_encode($this->getYears($car->mark_key, $car->model_key));
            $select = $year_key;
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors, 'items' => $items, 'select' => $select]);
        } else {
            die($this->message);
        }
    }
    
    /**
     * Проверка на дубликат
     * @param string $year_name
     * @param string $mark_key
     * @param string $model_key
     * @param string year_key
     * @return bool
     */
    private function _alreadyExist($year_name, $mark_key, $model_key, $year_key = 0) {
        $c = [
            'year_name' => $year_name,
            'model_key' => $model_key,
            'mark_key' => $mark_key,
        ];
        if (!empty($year_key)) {
            $c['year_key:!='] = $year_key;
        }
        $ae = $this->core->xpdo->getCount($this->classname, $c);
        return $ae != 0;
    }
}

/**
 * Обработка запроса на добавление/редактирование категории
 */
class UpdateCategory extends Catalog {
    
    public $classname = 'Brevis\Model\Category';
        
    public function run() {
        $errors = [];
        $this->message = $this->lang['form_error_msg'];
        if (empty($_REQUEST['name'])) {
            $errors['name'] = $this->lang['catalog.name_error_nf'];
        } else {
            $name = $this->core->cleanInput($_REQUEST['name']);
            if (empty($name)) {
                $errors['name'] = $this->lang['catalog.name_error_nf'];
            } else {
                if (empty($_REQUEST['key'])) {
                    // new
                    if ($this->_alreadyExist($name)) {
                        $errors['name'] = $this->lang['catalog.name_error_ae'];
                    } else {
                        if ($key = $this->avCode->getNextCategoryKey()) {
                            $category = $this->core->xpdo->newObject($this->classname, [
                                'key' => $key,
                                'name' => $name,
                            ]);
                            if ($category->save()) {
                                $this->success = true;
                                $this->message = $this->lang['saved'];
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        } else {
                            $errors['name'] = $this->avCode->getError();
                        }
                    }
                } else {
                    $key = $_REQUEST['key'];
                    if (!isset($_REQUEST['remove'])) {
                        // update
                        if ($this->_alreadyExist($name, $key)) {
                            $errors['name'] = $this->lang['catalog.name_error_ae'];
                        } else {
                            if (!$category = $this->core->xpdo->getObject($this->classname, ['key' => $key])) {
                                $this->message = $this->lang['catalog.name_error_category_key_incorrect'];
                            } else {
                                $category->set('name', $name);
                                if ($category->save()) {
                                    $this->success = true;
                                    $this->message = $this->lang['saved'];
                                } else {
                                    $this->message = $this->lang['save_db_error'];
                                    $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                                }
                            }
                        }
                    } else {
                        // remove
                        if ($items = $this->core->xpdo->getCount('Brevis\Model\Item', [
                            'category_key' => $key,
                        ])) {
                            $this->message = $this->lang['catalog.name_error_items_exist'];
                        } else {
                            // use get to remove aggregates
                            if ($o = $this->core->xpdo->getObject($this->classname, [
                                'key' => $key,
                            ]) and $o->remove()) {
                                $this->success = true;
                                $this->message = $this->lang['removed'];
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        }
                    }
                }
            }
        }
        // в случае успеха возвращаем новый список и ключ для подсветки
        $items = $select = '';
        if ($this->success) {
            $items = json_encode($this->getCategories());
            if (!isset($_REQUEST['remove'])) { $select = $key; }
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors, 'items' => $items, 'select' => $select]);
        } else {
            die($this->message);
        }
    }
    
    /**
     * Проверка на дубликат
     * @param string $name
     * @param string $key
     * @return bool
     */
    private function _alreadyExist($name, $key = 0) {
        $c = ['name' => $name];
        if (!empty($key)) {
            $c['key:!='] = $key;
        }
        $ae = $this->core->xpdo->getCount($this->classname, $c);
        return $ae != 0;
    }
}

/**
 * Обработка запроса на добавление/редактирование/удаление элемента
 */
class UpdateElement extends Catalog {
    
    public $classname = 'Brevis\Model\Element';
        
    public function run() {
        $errors = [];
        $this->message = $this->lang['form_error_msg'];
        if (empty($_REQUEST['name'])) {
            $errors['name'] = $this->lang['catalog.name_error_nf'];
        } else {
            $name = $this->core->cleanInput($_REQUEST['name']);
            if (empty($name)) {
                $errors['name'] = $this->lang['catalog.name_error_nf'];
            }
        }
        if (empty($_REQUEST['category_key'])) {
            $errors['name'] = $this->lang['catalog.name_error_category_key_nf'];
        } elseif (!$category = $this->core->xpdo->getObject('Brevis\Model\Category', [
                'key' => $_REQUEST['category_key']
            ])) { // get car with this mark_key
            $errors['name'] = $this->lang['catalog.name_error_category_key_incorrect']; 
        }
        $increase = 1; // default
        if (!empty($_REQUEST['increase']) and $this->_checkIncrease($_REQUEST['increase'])) {
            $increase = $_REQUEST['increase'];
        }
        if (empty($errors)) {
            if (empty($_REQUEST['key'])) {
                // new
                if ($this->_alreadyExist($name, $category->key)) {
                    $errors['name'] = $this->lang['catalog.name_error_ae'];
                } else {
                    if ($key = $this->avCode->getNextElementKey()) {
                        $element = $this->core->xpdo->newObject($this->classname, [
                            'key' => $key,
                            'name' => $name,
                            'category_key' => $category->key,
                            'increase_category' => $increase,
                            'increase_category_id' => $increase,
                        ]);
                        if ($element->save()) {
                            $this->success = true;
                            $this->message = $this->lang['saved'];
                        } else {
                            $this->message = $this->lang['save_db_error'];
                            $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                        }
                    } else {
                        $errors['name'] = $this->avCode->getError();
                    }
                }
            } else {
                $key = $_REQUEST['key'];
                if (!isset($_REQUEST['remove'])) {
                    // update
                    if ($this->_alreadyExist($name, $category->key, $key)) {
                        $errors['name'] = $this->lang['catalog.name_error_ae'];
                    } else {
                        if (!$element = $this->core->xpdo->getObject($this->classname, ['key' => $key, 'category_key' => $category->key])) {
                            $this->message = $this->lang['catalog.name_error_element_key_incorrect'];
                        } else {
                            $element->set('name', $name);
                            $element->set('increase_category', $increase);
                            $element->set('increase_category_id', $increase);
                            if ($element->save()) {
                                $this->success = true;
                                $this->message = $this->lang['saved'];
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        }
                    }
                } else {
                    // remove
                    if ($items = $this->core->xpdo->getCount('Brevis\Model\Item', [
                        'category_key' => $category->key,
                        'element_key' => $key,
                    ])) {
                        $this->message = $this->lang['catalog.name_error_items_exist'];
                    } else {
                        if ($this->core->xpdo->removeObject($this->classname, [
                            'category_key' => $category->key,
                            'key' => $key,
                        ])) {
                            $this->success = true;
                            $this->message = $this->lang['removed'];
                        } else {
                            $this->message = $this->lang['save_db_error'];
                            $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                        }
                    }
                }
            }
        }
        
        // в случае успеха возвращаем новый список и ключ для подсветки
        $items = $select = '';
        if ($this->success) {
            $items = json_encode($this->getElements($category->key));
            $select = $key;
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors, 'items' => $items, 'select' => $select]);
        } else {
            die($this->message);
        }
    }
    
    /**
     * Проверка на дубликат
     * @param string $name
     * @param string $category_key
     * @param string $key
     * @return bool
     */
    private function _alreadyExist($name, $category_key, $key = 0) {
        $c = ['name' => $name, 'category_key' => $category_key];
        if (!empty($key)) {
            $c['key:!='] = $key;
        }
        $ae = $this->core->xpdo->getCount($this->classname, $c);
        return $ae != 0;
    }
    
    private function _checkIncrease($id) {
        $ae = $this->core->xpdo->getCount('Brevis\Model\Increase', $id);
        return $ae != 0;
    }
}

/**
 * Обработка запроса на добавление/редактирование наценки
 * $key == $id
 * $name == $increase
 */
class UpdateIncrease extends Catalog {
    
    public $classname = 'Brevis\Model\Increase';
        
    public function run() {
        $errors = [];
        $this->message = $this->lang['form_error_msg'];
        if (empty($_REQUEST['name'])) {
            $errors['name'] = $this->lang['catalog.name_error_nf'];
        } else {
            $name = intval($_REQUEST['name']);
            if ($name <= 0) {
                $errors['name'] = $this->lang['catalog.name_error_nf'];
            }
        }
        if (empty($errors)) {
            if (empty($_REQUEST['key'])) {
                // new
                if ($this->_alreadyExist($name)) {
                    $errors['name'] = $this->lang['catalog.name_error_ae'];
                } else {
                    $element = $this->core->xpdo->newObject($this->classname, [
                        'increase' => $name,
                    ]);
                    if ($element->save()) {
                        $key = $element->id;
                        $this->success = true;
                        $this->message = $this->lang['saved'];
                    } else {
                        $this->message = $this->lang['save_db_error'];
                        $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                    }
                }
            } else {
                $key = $_REQUEST['key'];
                if (!$element = $this->core->xpdo->getObject($this->classname, $key)) {
                    $this->message = $this->lang['catalog.name_error_increase_key_incorrect'];
                } else {
                    if (!isset($_REQUEST['remove'])) {
                        // update
                        if ($this->_alreadyExist($name, $key)) {
                            $errors['name'] = $this->lang['catalog.name_error_ae'];
                        } else {
                            $element->set('increase', $name);
                            if ($element->save()) {
                                $this->success = true;
                                $this->message = $this->lang['saved'];
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        }
                    } else {
                        // remove
                        if ($element->allow_remove == 0) {
                            $this->message = $this->lang['catalog.name_error_key_notallowed'];
                        } else {
                            $increaseId = $element->id;
                            if ($element->remove()) {
                                // update elements
                                $updated = $this->_updateElements($increaseId);
                                $this->success = true;
                                $this->message = str_replace('{$updated}', $updated, $this->lang['catalog.elements_updated']);
                            }
                        }
                    }
                }
            }
        }
        
        // в случае успеха возвращаем новый список и ключ для подсветки
        $items = $select = '';
        if ($this->success) {
            $items = json_encode($this->getIncreases());
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors, 'items' => $items]);
        } else {
            die($this->message);
        }
    }
    
    /**
     * Проверка на дубликат
     * @param string $name
     * @param string $key
     * @return bool
     */
    private function _alreadyExist($name, $key = 0) {
        $c = ['increase' => $name];
        if (!empty($key)) {
            $c['id:!='] = $key;
        }
        $ae = $this->core->xpdo->getCount($this->classname, $c);
        return $ae != 0;
    }
    
    /**
     * Обновляет наценку для элементов
     * @param int $increaseId
     * @param int $newIncreaseId
     * @return int Кол-во обновленных элементов
     */
    private function _updateElements($increaseId, $newIncreaseId = 1) {
        // TODO updateCollection
        if ($items = $this->core->xpdo->getCollection('Brevis\Model\Element', ['increase_category_id' => $increaseId])) {
            foreach ($items as $item) {
                $item->set('increase_category_id', $newIncreaseId);
                $item->save();
            }
            return count($items);
        }
        return 0;
    }
}

/**
 * Обработка запроса на добавление/редактирование наценки
 * $key == $id
 * $name == $increase
 */
class UpdateBodytype extends Catalog {
    
    public $classname = 'Brevis\Model\BodyType';
        
    public function run() {
        $errors = [];
        $this->message = $this->lang['form_error_msg'];
        if (empty($_REQUEST['name'])) {
            $errors['name'] = $this->lang['catalog.name_error_nf'];
        } else {
            $name = $this->core->cleanInput($_REQUEST['name']);
            if (empty($name)) {
                $errors['name'] = $this->lang['catalog.name_error_nf'];
            }
        }
        if (empty($errors)) {
            if (empty($_REQUEST['key'])) {
                // new
                if ($this->_alreadyExist($name)) {
                    $errors['name'] = $this->lang['catalog.name_error_ae'];
                } else {
                    $element = $this->core->xpdo->newObject($this->classname, [
                        'name' => $name,
                    ]);
                    if ($element->save()) {
                        $key = $element->id;
                        $this->success = true;
                        $this->message = $this->lang['saved'];
                    } else {
                        $this->message = $this->lang['save_db_error'];
                        $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                    }
                }
            } else {
                $key = $_REQUEST['key'];
                if (!$element = $this->core->xpdo->getObject($this->classname, $key)) {
                    $this->message = $this->lang['catalog.name_error_key_incorrect'];
                } else {
                    if (!isset($_REQUEST['remove'])) {
                        // update
                        if ($this->_alreadyExist($name, $key)) {
                            $errors['name'] = $this->lang['catalog.name_error_ae'];
                        } else {
                            $element->set('name', $name);
                            if ($element->save()) {
                                $this->success = true;
                                $this->message = $this->lang['saved'];
                            } else {
                                $this->message = $this->lang['save_db_error'];
                                $this->core->log('save_db_error ' . $this->classname . ':' . print_r($_REQUEST, true));
                            }
                        }
                    } else {
                        // remove
                        $id = $element->id;
                        if ($element->remove()) {
                            // update elements
                            $updated = $this->_updateElements($id);
                            $this->success = true;
                            $this->message = $this->lang['removed'] . '. ' . str_replace('{$updated}', $updated, $this->lang['catalog.items_updated']);
                        }
                    }
                }
            }
        }
        
        // в случае успеха возвращаем новый список и ключ для подсветки
        $items = $select = '';
        if ($this->success) {
            $items = json_encode($this->getBodytypes());
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors, 'items' => $items]);
        } else {
            die($this->message);
        }
    }
    
    /**
     * Проверка на дубликат
     * @param string $name
     * @param string $key
     * @return bool
     */
    private function _alreadyExist($name, $key = 0) {
        $c = ['name' => $name];
        if (!empty($key)) {
            $c['id:!='] = $key;
        }
        $ae = $this->core->xpdo->getCount($this->classname, $c);
        return $ae != 0;
    }
    
    /**
     * Обновляет тип кузова для товаров
     * @param int $oldId
     * @param int $newId
     * @return int Кол-во обновленных элементов
     */
    private function _updateElements($oldId, $newId = 0) {
        // TODO updateCollection
        if ($items = $this->core->xpdo->getCollection('Brevis\Model\Item', ['body_type' => $oldId])) {
            foreach ($items as $item) {
                $item->set('body_type', $newId);
                $item->save();
            }
            return count($items);
        }
        return 0;
    }
}