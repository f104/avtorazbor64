<?php

/**
 * Управление товарами
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;
use Brevis\Components\EventLogger\EventLogger as EventLogger;
use Brevis\Components\Robokassa\Robokassa as Robokassa;
use Picqer\Barcode\BarcodeGeneratorPNG as BarcodeGenerator;

class Orders extends Controller {

    public $name = 'Заказы';
    public $permissions = ['orders_view'];
    public $langTopic = 'order';
    public $classname = 'Brevis\Model\Order';

    /**
     * Пользователь - поставщик
     * @var Brevis\Model\Supplier
     */
    public $supplier = false;
    public $buyer = false;
    public $isManager = false; // работает менеджер/админ
    
    /**
     * @var array [id]=> cols
     */
    public $sklads = [];
    public $skladsSelect = []; // склады для селектов
    
    public $statuses, $statusesSelect;
    
    public $additionalJs = [
        '/assets/js/orders.js',
    ];


    public $limit = PROJECT_DEFAULT_QUERY_LIMIT;
    public $page = 1;
    private $_offset = 0;
    private $_total = 0;
    
    public $allowedFilters = ['status_id'];
    public $allowedSort = ['id', 'createdon', 'item_id', 'sklad_prefix', 'item_price', 'cost', 'user_id'];
    public $defaultSort = 'id';
    public $sortdir = 'DESC';
        
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->statuses = $this->getStatuses();
        $this->statusesSelect = $this->getStatusesSelect();
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['orders'], $this->statusesSelect);
        $this->eventLogger->langPrefix = 'order.';
        $this->isManager = $this->core->authUser->checkPermissions('orders_manage');
        if (!$this->isManager) {
            switch ($this->core->authUser->getUserGroup()) {
                case 1: $this->buyer = $this->core->authUser; break;
                case 3: $this->supplier = $this->core->authUser->getOne('UserSupplier'); break;
            }
        }
        // Выберем склады, возьмем только те, что участвуют в выдаче. Это позволит избежать замены товара на товар с неопубикованного склада
        $c = $this->core->xpdo->newQuery('\Brevis\Model\Sklad');
        $c->select($this->core->xpdo->getSelectColumns('\Brevis\Model\Sklad', 'Sklad'));
        $c->leftJoin('\Brevis\Model\SkladStatus', 'Status');
        $c->sortby('Sklad.name');
//        $c->where([
//            'Sklad.switchon' => 1,
//            'Status.show' => 1,
//        ]);
        if ($this->supplier) {
            $c->where(['supplier_id' => $this->supplier->id]);
        }
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->sklads[$row['id']] = $row;
                $this->skladsSelect[$row['id']] = $row['name'].' ('.$row['prefix'].')';
            }
        } else {
            $this->core->log('Не могу выбрать Sklad:' . print_r($c->stmt->errorInfo(), true));
        }
        $this->_readFilters($_GET);
        // data for export
        if ($this->isManager) {
            $this->exportColumns = ['id', 'status_name', 'item_name', 'item_code', 'user_name', 'sklad_prefix', 'item_price', 'cost', 'createdon'];
        }
        if ($this->supplier) {
            $this->exportColumns = ['id', 'status_name', 'item_name', 'item_code', 'sklad_prefix', 'item_price', 'createdon', 'status_id'];
        }
        if ($this->buyer) {
            $this->exportColumns = ['id', 'status_name', 'item_name', 'cost', 'createdon'];
        }
    }
    
    /**
     * Возвращает список статусов
     * @return array
     */
    protected function getStatuses() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\OrderStatus');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\OrderStatus', 'OrderStatus'));
        $c->sortby('OrderStatus.order','ASC');
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            $results = [];
            foreach ($rows as $row) {
                $results[$row['id']] = $row;
            }
            return $results;
        } else {
            $this->core->log('Не могу выбрать OrderStatus:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Возвращает список статусов для селекта
     * @return array
     */
    public function getStatusesSelect() {
        $statusesSelect = [];
        foreach ($this->statuses as $item) {
            $statusesSelect[$item['id']] = $item['name'];
        }
        return $statusesSelect;
    }
    
    /** 
     * Выборка данных
     * @param bool $raw Флаг для выборки данных, используется при экспорте, снимает лимит и пажинацию
     */
    public function getRows($raw = false) {
        $items = [];
        if ($this->supplier) {
            $this->where['sklad_id:IN'] = !empty($this->sklads) ? array_keys($this->sklads) : [0];
        }
        if ($this->buyer) {
            $this->where['user_id'] = $this->buyer->id;
        }
        foreach ($this->filters as $k => $v) {
            if ($k != 'page') {
                $this->where[$k] = $v;
            }
        }
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->where($this->where);
        $this->_total = $this->core->xpdo->getCount($this->classname, $c);
        if ($this->page < 1 or $this->page > ceil($this->_total / $this->limit)) {
            $this->page = 1;
            $this->filters['page'] = 1;
        }
        if (!$raw) {
            $this->_offset = $this->limit * ($this->page - 1);
            $c->limit($this->limit, $this->_offset);
        }
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Order'));
        $c->leftJoin('Brevis\Model\OrderStatus', 'Status', ('Status.id = Order.status_id'));
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\OrderStatus', 'Status', 'status_', ['name', 'fixed', 'allow_payment']));
        $c->leftJoin('Brevis\Model\User', 'User', ('User.id = Order.user_id'));
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\User', 'User', 'user_', ['name']));
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname .':' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /**
     * список заказов (пользователя или всех)
     */
    public function run() {
        // фильтры
        $filters = new FormFilters([], $this);
        $filters->select('status_id')->addLabel($this->lang['order.status_id'])->setSelectOptions($this->statusesSelect);
        $items = $this->getRows();
        $cols = [];
        $cols['id'] = ['title' => $this->lang['order.id'], 'tpl' => '@INLINE <a href="orders/view?id={$row.id}&'.http_build_query($this->filters).'">#{$row.id}</a>&nbsp;<span class="order-status-{$row.status_id} js-order-label-{$row.id}">{$row.status_name}</span>'];
        if ($this->isManager or $this->supplier) {
            $cols['item_id'] = ['title' => $this->lang['order.item_id'], 'tpl' => '@INLINE {if $row.is_paid == 0 and $row.status_id == 1}<a href="orders/itemview?id={$row.id}&'.http_build_query($this->filters).'" title="замена товара">{$row.item_name}</a>{else}{$row.item_name}{/if}'];
        } else {
            $cols['item_id'] = ['title' => $this->lang['order.item_id'], 'tpl' => '@INLINE <a href="cars/item?id={$row.item_id}">{$row.item_name}</a>'];
        }
        if ($this->isManager) {
            $cols['user_id'] = ['title' => $this->lang['order.user_id'], 'tpl' => '@INLINE <a class="js-ajaxpopup" href="users/view?id={$row.user_id}&popup=1">{$row.user_name}</a>'];
        }
        if ($this->isManager or $this->supplier) {
            $cols['sklad_prefix'] = ['title' => $this->lang['order.sklad_prefix']];
            $cols['item_price'] = ['title' => $this->lang['order.item_price']];
        }
        if ($this->isManager or $this->buyer) {
            $cols['cost'] = ['title' => $this->lang['order.cost']];
        }
//        if ($this->isManager) {
//            $cols['payment'] = ['title' => $this->lang['order.payment'], 'sortable' => false];
//        }
        $cols['createdon'] = ['title' => $this->lang['order.createdon'], 'tpl' => '@INLINE {$row.createdon|date:"d-m-Y H:i"}'];
        $statuses = ['<option value="0"></option>'];
        foreach ($this->statuses as $k=>$v) {
            if ($this->isManager or empty($v['permission']) or $this->core->authUser->checkPermissions($v['permission'])) {
                $statuses[] = '<option value="'.$k.'">'.$v['name'].'</option>';
            }
        }
        $statuses = implode('', $statuses);
        $statuses = '<form class="status-form" data-id="{$row.id}" title="Изменить статус заказа"><select class="form-control input-sm">'.$statuses.'</select></form>';
        $cols['moderate'] = ['title' => $this->lang['order.moderate'], 'sortable' => false, 'class' => 'text-nowrap'];
        if ($this->isManager) {
            $cols['moderate']['tpl'] = '@INLINE ' . $statuses;
            $cols['payment_form'] = ['title' => '', 'sortable' => false, 'class' => 'text-nowrap', 'tpl' => '@INLINE '
                . '<a href="orders/payment?order_id={$row.id}" class="btn btn-primary btn-xs js-ajaxpopup" title="Ввести оплату по заказу"><i class="fa fa-rub"></i></a> '
                . '<button class="btn btn-xs btn-danger js-item-remove" data-id="{$row.id}" title="Удалить"><i class="fa fa-trash-o"></i></button>'];
        }
        if ($this->supplier) {
            $cols['moderate']['tpl'] = '@INLINE {if $row.status_fixed == 0}'.$statuses.'{/if}';
        }
        if ($this->buyer) {
//            $this->_getPaymentForm($items, $this->buyer);
//            $cols['payment_form'] = ['title' => '', 'sortable' => false,];
//            $cols['moderate'] = ['title' => '', 'sortable' => false, 'tpl' => '@INLINE {if $row.status_id in [1,4]}{*новый или одобрен*}<button class="btn btn-xs btn-warning js-order-status" data-id="{$row.id}" data-status="2" title="Отменить заказ"><i class="fa fa-close"></i></button>{/if} '];
            $cols['payment_form'] = ['title' => '', 'sortable' => false,
                'tpl' => '@INLINE {if $row.status_allow_payment == 1}<button id="payment_{$row.id}" class="btn btn-xs btn-primary js-order-withdraw" data-id="{$row.id}">Оплатить заказ</button>{/if} '];
            $cols['moderate']['tpl'] = '@INLINE {if $row.status_id in [1,4]}{*новый или ожидает оплаты*}<button class="btn btn-xs btn-warning js-order-status" data-id="{$row.id}" data-status="2" title="Отменить заказ"><i class="fa fa-close"></i></button>{/if} ';
        }
        
        // форма удаления
        $formRemove = new Form([
            'id' => 'remove_form',
            'class' => 'white-popup-block mfp-hide',
            'action' => 'orders/remove'
        ], $this);
        $formRemove->legend($this->lang['order.confirm_remove']);
        $formRemove->hidden('id');
        $formRemove->button('Удалить', ['type' => 'submit', 'class' => 'btn btn-danger']);
        $formRemove->link('Отмена', ['href' => '#']);
        
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $items,
            'total' => $this->_total,
            'offset' => $this->_offset,
            'pagination' => $this->getPagination($this->_total, $this->page, $this->limit),
        ], $this);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $content]);
        }
        $data = [
            'content' => $content.$formRemove->draw(),
        ];
        return $this->template($this->template, $data, $this);        
    }
    
    private function getPayments(&$items) {
        if (empty($items)) { return; }
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $item['id'];
        }
        $c = $this->core->xpdo->newQuery('Brevis\Model\Payment');
        $c->where(['order_id:IN' => $ids]);
        $c->groupby('order_id');
        $c->select('`order_id`, SUM(`sum`)');
//        $c->prepare(); echo $c->toSQL(); return;
        if ($c->prepare() && $c->stmt->execute()) {
            $payments = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            foreach ($items as &$item) {
                if (array_key_exists($item['id'], $payments)) {
                    $item['payment'] = $payments[$item['id']];
                }
            }
        } else {
            $this->core->log('Не могу выбрать Payment:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * 
     * @param array $items список заказов
     * @param Brevis\Model\User $user пользователь, чьи заказы
     */
    private function _getPaymentForm(&$items, $user) {
        $Robo = new Robokassa($this->core);
        foreach ($items as &$item) {
            if ($item['status_allow_payment'] != 1) {
                $item['payment_form'] = '';
            } else {
                if (empty($item['payment'])) {
                    $item['payment'] = 0;
                }
                $item['payment_form'] = $Robo->paymentForm($item['id'], $item['item_name'], $item['cost'] - $item['payment'], $user->id, $user->email);
            }
        }
    }
    
}

/**
 * Удаляет заказ. Могут только администраторы.
 */
class Remove extends Orders {
    public $permissions = ['orders_manage'];
    public function run() {
        // проверим существование заказа
        if (!empty($_REQUEST['id']) and $order = $this->core->xpdo->getObject('Brevis\Model\Order', $_REQUEST['id'])) {
            $order->refundPaid('Удаление заказа');
            if ($order->remove()) {
                $this->success = true;
                $this->message = 'Удалено';
                $this->eventLogger->remove($order->id);
            }
        } else {
            $this->message = $this->lang['order.status_notfound'];
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            die($this->message);
        }
    }
}

/**
 * Процесс оплаты заказа покупателем списанием с баланса
 */
class Withdraw extends Orders {
    public function run() {
        $data = []; // доп. данные, возвращаемые аяксом
        // проверим заказ
        if (!empty($_REQUEST['id']) and $order = $this->core->xpdo->getObject('Brevis\Model\Order', $_REQUEST['id'])) {
            // принадлежность заказа
            if ($order->user_id == $this->core->authUser->id) {
                if ($status = $order->runWithdraw()) {
                    $this->success = true;
                    $this->message = 'Сохранено';
                    $data['new_status_name'] = $status->name;
                    $order->notifyBuyer($this);
                    $order->notifySupplierPaid($this);
                } else {
                    $this->message = $this->lang['order.withdraw_error'];
                }
            } else {
                $this->message = $this->lang['order.access_denied'];
            }
        } else {
            $this->message = $this->lang['order.order_notfound'];
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, $data);
        } else {
            die($this->message);
        }
    }
}

class Status extends Orders {
    public function run() {
        $data = []; // доп. данные, возвращаемые аяксом
        // проверим существование заказа и статуса
        if (!empty($_REQUEST['id']) and !empty($_REQUEST['status']) and $order = $this->core->xpdo->getObject('Brevis\Model\Order', $_REQUEST['id']) and $status = $this->core->xpdo->getObject('Brevis\Model\OrderStatus', $_REQUEST['status'])) {
            if ($order->status_id != $status->id) {
                // проверим разрешения
                $allow = false;
                if ($this->isManager) {
                    $allow = true;
                }
                if ($this->supplier) {
                    if (array_key_exists($order->sklad_id, $this->sklads) and $order->fixed == 0) {
                        $allow = true;
                    }
                }
                if ($this->buyer and $status->id == 2 and !$order->isPaid()) {
                    // покупатель может только отменить неоплаченный заказ
                    $allow = true;
                }
                if ($allow) {
    //                $orderStatus = $order->getOne('Status'); // нельзя, не сохраняется тогда $order, какая-то связь...
    //                $orderStatus = $this->core->xpdo->getObject('Brevis\Model\OrderStatus', $order->status_id);
                    if ($status->order < 10) {
                        $order->refundPaid('Отмена заказа');
                    }
                    // некоторые статусы изменять нельзя
    //                if ($orderStatus->fixed != 1) {
                        $loggerOld = $order->toArray();
                        $order->set('status_id', $status->id);
                        $order->set('updatedon', date('Y-m-d H:i:s',  time()));
                        if ($order->save()) {
                            $this->success = true;
                            $this->message = 'Сохранено';
                            if ($status->allow_payment === 1) {
                                if (!$order->isPaid()) {
                                    // инициируем процесс оплаты
                                    if ($withdraw = $order->runWithdraw()) {
                                      $status = $withdraw;
        //                              $this->message = $status->id;
                                    }
                                } else {
                                    // повторный запрос "ожидает оплаты"
                                    $status = $this->core->xpdo->getObject('Brevis\Model\OrderStatus', $order->paidStatus);
                                    $order->set('status_id', $status->id);
                                    $order->save();
                                }
                             }
                            $data['new_status_name'] = $status->name;
                            $order->notifyBuyer($this);
                            if ($this->buyer and $status->id == 2) {
                                $order->notifySupplierCancelled($this);
                                $data['remove_payment_form'] = true;
                            }
                            $this->eventLogger->update($order->id, $loggerOld, $order->toArray());
                        }
    //                } else {
    //                    $this->message = $this->lang['order.status_error'];
    //                }
                } else {
                    $this->message = $this->lang['order.access_denied'];
                }
            } else {
                $this->message = $this->lang['order.error_nochange'];                
            }
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, $data);
        } else {
            die($this->message);
        }
    }
    
}

class Itemview extends Orders {
    
    public $name = 'Товар для заказа';
    public $permissions = ['orders_edit'];
    public $template = 'orders.itemview';
    public $langTopic = 'item,order';
    
    // общие условия для выборки Items, чтобы не предлагал неопубликованные или неотмодерированные
    public $where;
    
    /** @var Brevis\Model\Order */
    public $order = null;
    /** @var Brevis\Model\Item */
    public $item = null;
    /** "мфк bool Разрешена или нет замена товара (зависит от статуса */
    public $allowReplace = false;
    
    public $itemsTemplate = 'orders.itemview.items'; // шаблон списка элементов

    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        if (empty($_REQUEST['id']) or !$this->order = $this->core->xpdo->getObject('Brevis\Model\Order', $_REQUEST['id']) or !$this->item = $this->order->getOne('Item') or (!$this->isManager and !array_key_exists($this->item->sklad_id, $this->sklads))) {
            $this->redirect('parent');
        }
        if (!$this->order->is_paid) {
            $this->allowReplace = true;
        }
        
        $this->name .= ' #'.$this->order->id;
        
        $this->where = [
            'Item.published' => 1,
            'Item.moderate' => 1,
            [
                'Item.reserved' => 0,
                'OR:Item.id:=' => $this->order->item_id,
            ],
            'Item.sklad_id:IN' => array_keys($this->sklads),
        ];
    }
    
    public function run() {
        
        if ($this->allowReplace) {
            // форма выбора детали
            $formCCE = new Form([
                'id' => 'order_form',
            ], $this);
            $formCCE->hidden('id')->setValue($this->order->id);
            $formCCE->select('mark_key', ['required'])->addLabel($this->lang['item.mark'])
                ->setSelectOptions($this->_getMarks())
                ->setValue($this->item->mark_key);
            $formCCE->select('model_key', ['required'])->addLabel($this->lang['item.model'])
                ->withEmptyOption()
                ->setSelectOptions($this->getModels($this->item->mark_key))
                ->setValue($this->item->model_key.$this->item->year_key);
            $formCCE->select('category_key', ['required'])->addLabel($this->lang['item.category'])
                ->withEmptyOption()
                ->setSelectOptions($this->getCategories($this->item->mark_key, $this->item->model_key, $this->item->year_key))
                ->setValue($this->item->category_key);
            $formCCE->select('element_key', ['required'])->addLabel($this->lang['item.element'])
                ->withEmptyOption()
                ->setSelectOptions($this->getElements($this->item->mark_key, $this->item->model_key, $this->item->year_key, $this->item->category_key))
                ->setValue($this->item->element_key);
            $formCCE->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        }
             
        $data = [
            'allowReplace' => $this->allowReplace,
            'formCCE' => $this->allowReplace ? $formCCE->draw() : '',
            'items' => $res = $this->template($this->itemsTemplate, [
                'items' => $this->getItems($this->item->mark_key, $this->item->model_key, $this->item->year_key, $this->item->category_key, $this->item->element_key),
                'order' => $this->order->toArray(),
            ]),
        ];
        return $this->template($this->template, $data, $this);
    }
    
    /**
     * Выборка марок авто
     * 
     * @return array
     */ 
    private function _getMarks() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select('mark_key');
        $c->where($this->where);
        $c->distinct();
//        $c->prepare(); var_dump($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $keys = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
            $c->select(['mark_key','mark_name']);
            $c->distinct();
            $c->sortby('mark_name', 'ASC');
            $c->where(['mark_key:IN' => $keys]);
    //        $c->prepare(); var_dump($c->toSQL());
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            } else {
                $this->core->logger->error('Не могу выбрать marks:' . print_r($c->stmt->errorInfo(), true));
            }
        } else {
            $this->core->logger->error('Не могу выбрать marks:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;        
    }
    
    /**
     * Выборка моделей авто
     * @param string mark_key
     * @return array
     */
    public function getModels($mark_key) {
        $models = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select(['model_key','year_key']);
        $c->groupby('model_key, year_key');
        $where = ['Item.mark_key'=> $mark_key];
        $where = array_merge($where, $this->where);
        $c->where($where);
        if ($c->prepare() && $c->stmt->execute()) {
            $keys = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            $tmp = [];
            foreach ($keys as $key) {
                $tmp[$key['model_key']][] = $key['year_key'];
            }
            $keys = $tmp;
            $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Cars', 'Cars'));
            $c->distinct();
            $c->sortby('model_name', 'ASC');
            $where = ['mark_key'=> $mark_key, 'model_key:IN'=> array_keys($keys)]; //, 'Item.year_key' => 'Cars.year_key'
//            $where[] = "Item.year_key = LPAD(Cars.year_key,2,'0')"; //FUCK!
            $c->where($where);
//            $c->prepare(); var_dump($c->toSQL());
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    if (in_array($row['year_key'], $keys[$row['model_key']])) {
                        $row['year_key'] = sprintf("%'.02d", $row['year_key']);
                        $models[$row['model_key'].$row['year_key']] = $row['year_name'] ?: $row['model_name'];
                    }
                }
            } else {
                $this->core->log('Не могу выбрать models:' . print_r($c->stmt->errorInfo(), true));
            }
        } else {
            $this->core->log('Не могу выбрать models:' . print_r($c->stmt->errorInfo(), true));
        }
        return $models;
    }
    
    /**
     * Выборка категорий
     * 
     * @param string $mark_key
     * @param string $model_key
     * @param string $year_key
     * 
     * @return array
     */
    public function getCategories($mark_key, $model_key, $year_key) {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select('category_key');
        $c->distinct();
        $where = [
            'mark_key' => $mark_key,
            'model_key' => $model_key,
            'year_key' => empty($year_key) ? '' : $this->formatYearKey($year_key),
        ];
        $where = array_merge($where, $this->where);
        $c->where($where);
        if ($c->prepare() && $c->stmt->execute()) {
            $keys = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            $c = $this->core->xpdo->newQuery('Brevis\Model\Category');
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Category', 'Category', '', ['key','name']));
            $c->sortby('name', 'ASC');
            $c->where(['key:IN' => $keys]);
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            } else {
                $this->core->log('Не могу выбрать category:' . print_r($c->stmt->errorInfo(), true));
            }
        } else {
            $this->core->log('Не могу выбрать category:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    /**
     * Выборка элементов
     *
     * @params string $mark_key, $model_key, $year_key, $category_key
     * @return array
     */
    public function getElements($mark_key, $model_key, $year_key, $category_key) {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select('element_key');
        $c->distinct();
        $where = [
            'Item.mark_key'=> $mark_key, 
            'Item.model_key'=> $model_key,
            'Item.year_key' => empty($year_key) ? '' : $this->formatYearKey($year_key),
            'Item.category_key'=> $category_key,
        ];
        $where = array_merge($where, $this->where);
        $c->where($where);
        if ($c->prepare() && $c->stmt->execute()) {
            $keys = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            $c = $this->core->xpdo->newQuery('Brevis\Model\Element');
            $c->select(['key','name']);
            $c->sortby('name', 'ASC');
            $c->where(['key:IN' => $keys]);
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            } else {
                $this->core->log('Не могу выбрать element:' . print_r($c->stmt->errorInfo(), true));
            }
        } else {
            $this->core->log('Не могу выбрать element:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    /**
     * выборка items
     * @param @params string $mark_key, $model_key, $year_key, $category_key, $element_key
     * @return array
     */
    public function getItems($mark_key, $model_key, $year_key, $category_key, $element_key) {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Item', 'Item'));
        $c->sortby('name', 'ASC');
        $where = [
            'Item.mark_key'=> $mark_key, 
            'Item.model_key'=> $model_key,
            'Item.year_key' => empty($year_key) ? '' : $this->formatYearKey($year_key),
            'Item.category_key'=> $category_key,
            'Item.element_key'=> $element_key,
        ];
        $where = array_merge($where, $this->where);
        $c->where($where);
//        $c->prepare(); var_dump($c->toSQL()); exit;
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            if (!empty($rows)) {
            
                // индексируем массив по id
                $rowsIndexed = $idx = array();
                $ids = array(); // массив для выборки фотографий
                foreach ($rows as $item) {
                    $ids[] = $item['id'];
                    $rowsIndexed[$item['id']] = $item;
                }
                $rows = $rowsIndexed;

                // images
                $images = array();
                $c = $this->core->xpdo->newQuery('Brevis\Model\ItemImages', array('item_id:IN' => $ids, 'binary:IS'=>null));
                $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\ItemImages', 'ItemImages', '', array('id','item_key','item_id','filename','url','prefix')));
    //            $c->prepare(); var_dump($c->toSQL()); exit;
                if ($c->prepare() && $c->stmt->execute()) {
                    $images = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
                    if (!empty($images)) {
//                        $idx = 0;
                        foreach ($images as $item) {
                            $rows[$item['item_id']]['images'][] = $item;
//                            $idx++;
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
    
}

class Get extends Itemview {
    public function run() {
        $template = 'orders.itemview.options';
        $res = [];
        if (!empty($_REQUEST['mark_key'])) {
            if (!empty($_REQUEST['model_key']) and !empty($_REQUEST['year_key'])) {
                if (!empty($_REQUEST['category_key'])) {
                    if (!empty($_REQUEST['element_key'])) {
                        // items
                        $res = $this->getItems($_REQUEST['mark_key'], $_REQUEST['model_key'], $_REQUEST['year_key'], $_REQUEST['category_key'], $_REQUEST['element_key']);
                        $template = $this->itemsTemplate;
                    } else {
                        // элементы
                        $res = $this->getElements($_REQUEST['mark_key'], $_REQUEST['model_key'], $_REQUEST['year_key'], $_REQUEST['category_key']);
                    }
                } else {
                    // категории
                    $res = $this->getCategories($_REQUEST['mark_key'], $_REQUEST['model_key'], $_REQUEST['year_key']);
                }
            } else {
                // модели
                $res = $this->getModels($_REQUEST['mark_key']);
            }
        }
        if ($this->isAjax) {
            $res = $this->template($template, ['items' => $res, 'order' => $this->order->toArray()]);
            $this->core->ajaxResponse(true, '', ['results' => $res]);
        } else {
            return $res;
        }
    }
}

class Replaceitem extends Itemview {
    
    public function run() {
        $data = [];
        if (!empty($_REQUEST['new_item']) and $newitem = $this->core->xpdo->getObject('Brevis\Model\Item', array_merge(['id' => $_REQUEST['new_item']], $this->where)) and ($this->isManager or array_key_exists($newitem->sklad_id, $this->sklads))) {
            if ($order = $this->core->xpdo->getObject('Brevis\Model\Order', $_REQUEST['id'])) {
                // проверяем "фиксацию статуса"
                if ($this->allowReplace) {
                    $newitemArray = $newitem->toArray();
                    $element = $this->core->xpdo->getObject('Brevis\Model\Element', ['key' => $newitemArray['element_key']]);
                    $newitemArray['city_id'] = $this->sklads[$newitemArray['sklad_id']]['city_id'];
                    $newitemArray['region_id'] = $this->sklads[$newitemArray['sklad_id']]['region_id'];
                    $newitemArray['increase_category'] = $element->increase_category;
                    
                    // снимаем с резерва старый товар
                    $olditem = $this->core->xpdo->getObject('Brevis\Model\Item', $order->item_id);
                    $olditem->set('reserved', 0);
                    $olditem->save();
                    
                    // изменяем заказ
                    $order->set('item_id', $newitemArray['id']);
                    $order->set('item_name', $newitemArray['name']);
                    $order->set('item_price', $newitemArray['price']);
                    $order->set('item_code', $newitemArray['code']);
                    $order->set('sklad_id', $newitemArray['sklad_id']);
                    $order->set('sklad_prefix', $newitemArray['prefix']);
                    $order->set('sklad_city_id', $newitemArray['city_id']);
                    $order->set('cost', $this->core->calculatePrice($newitemArray, 
                        $this->core->xpdo->getObject('Brevis\Model\User', $order->user_id)));
                    $order->set('updatedon', date('Y-m-d H:i:s',  time()));
                    $order->save();
                    
                    // ставим новый товар в резерв
                    $newitem->set('reserved', 1);
                    $newitem->save();
                    
                    $this->redirect($this->makeUrl('orders', $this->filters));                    
                } else {
                    $this->message = $this->lang['order.status_error'];                    
                }
            } else {
                $this->message = $this->lang['order.order_notfound'];
            }
        } else {
            $this->message = $this->lang['order.access_denied'];
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse(!$this->success, $this->message);
        } else {
            return $this->message;
        }
    }
    
}

class View extends Orders {
    
    public $template = 'orders.view';
    public $name = 'Заказ';
    
    public function run() {
        if (isset($_REQUEST['id']) and $order = $this->core->xpdo->getObject('Brevis\Model\Order', $_REQUEST['id'])) {
            $orderArray = $order->toArray();
            $this->name .= ' #'.$orderArray['id'];
            $orderArray['sklad_prefix'] = '<a href="sklads/view?id='.$orderArray['sklad_id'].'">'.$orderArray['sklad_prefix'].'</a>';
            $orderArray['status_id'] = '<span class="order-status-'.$orderArray['status_id'].'">'.$this->statusesSelect[$orderArray['status_id']].'</span>';
            if ($this->isManager) {
                $user = $order->getOne('User');
                $orderArray['user_id'] = '<a href="users/view?id='.$user->id.'">'.$user->name.'</a>';
                $from = $this->core->xpdo->getObjectGraph('Brevis\Model\City', ['Region'], $orderArray['sklad_city_id']);
                $orderArray['sklad_city_id'] = $from->Region->name.', '.$from->name;
                $to = $this->core->xpdo->getObjectGraph('Brevis\Model\City', ['Region'], $orderArray['user_city_id']);
                $orderArray['user_city_id'] = $to->Region->name.', '.$to->name;
            } else {
                unset($orderArray['user_id'], $orderArray['sklad_city_id'], $orderArray['user_city_id']);
                if ($this->supplier) {
                    unset($orderArray['cost']);
                }
                if ($this->buyer) {
                    unset($orderArray['item_price'], $orderArray['sklad_prefix'], $orderArray['remote_id']);
                }
            }
            if (empty($orderArray['remote_id'])) { unset($orderArray['remote_id']); }
            if (empty($orderArray['comment'])) { unset($orderArray['comment']); }
            unset($orderArray['item_id'], $orderArray['sklad_id']);
            $barcodeGenerator = new BarcodeGenerator;
            $data = [
                'content' => $this->template('_table.info', ['rows' => $orderArray, 'prefix' => 'order.'], $this),
                'barcodeBase64' => base64_encode($barcodeGenerator->getBarcode($order->item_code, $barcodeGenerator::TYPE_CODE_128)),
            ];
            return $this->template($this->template, $data, $this);
        } else {
            $this->core->sendErrorPage();
        }
    }
    
}

/**
 * Ввод проплаты по заказу
 */
class Payment extends Orders {
    
    public $name = 'Оплата заказа';
    public $permissions = ['fees_add'];
    public $langTopic = 'order,fee';
    
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['fees']);
        $this->eventLogger->langPrefix = 'fee.';
    }

    public function run() {
        
        if (empty($_REQUEST['order_id']) or !$order = $this->core->xpdo->getObject('Brevis\Model\Order', $_REQUEST['order_id'])) {
            $this->core->sendErrorPage();
        }
        
        if ($order->isPaid()) {
            $this->message = $this->lang['order.already_paid'];
        } else {
            if ($this->statuses[$order->status_id]['allow_payment'] == 0) {
                $this->message = $this->lang['order.status_error'];                
            }
        }
        
        if (empty($this->message)) {
        
            $item = $this->core->xpdo->newObject('Brevis\Model\Fee');
            $form = $this->form($item, $order);

            if ($form->process()) {
                $fields = $form->getValues();
    //            var_dump($fields); die;
                // добавим время?
                if (!$form->hasError()) {
                    $item->fromArray($fields);
                    $item->save();
                    $this->eventLogger->add($item->id);
                    $this->success = true;
                    $this->message = 'Сохранено';

                    $status = $order->runWithdraw();
                    $order->notifyBuyer($this); 
                    $order->notifySupplierPaid($this); 

                    if ($this->isAjax) {
                        $this->core->ajaxResponse($this->success, $this->message, 
                            ['update' => [
                                '.js-order-label-'.$order->id => $status->name]
                            ]);
                    }
                    $this->redirect($this->makeUrl('parent', $this->filters));
                } else {
                    $this->message = 'Исправьте ошибки в форме.';
                }

                if ($this->isAjax) {
                    $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
                }
            }
            
        }
        
        $data = [
            'content' => empty($this->message) ? $form->draw() : '',
        ];
        if ($this->isAjax) {
            $output = $this->template('_ajax', $data, $this);
            $this->core->ajaxResponse(true, null, ['content' => $output]);
        } else {
            return $this->template($this->template, $data, $this);            
        }
    }

    /**
     * Форма ввода платежа по заказу
     * @param \Brevis\Model\Fee $fee
     * @param \Brevis\Model\Order $order
     * @return \Brevis\Components\Form
     */
    protected function form(\Brevis\Model\Fee $item, \Brevis\Model\Order $order) {
        $form = new Form([
            'id' => 'payment_form',
            'class' => 'js-ajaxform',
            'action' => 'orders/payment'
        ], $this);
        $form->hidden('user_id', [
            'required',
        ])->setValue($order->user_id);
        $form->hidden('order_id', [
            'required',
        ])->setValue($order->id);
        $form->input('order', [
            'readonly',
        ])->addLabel($this->lang['fee.order_id'])->setValue('#'.$order->id.' ('.$order->item_name.')');
        $form->input('timestamp', [
            'required',
            'class' => 'js-datepicker form-control',
        ])->addLabel($this->lang['fee.timestamp'])
          ->setValue($item->timestamp == 'CURRENT_TIMESTAMP' ? date('d-m-Y') : date('d-m-Y', strtotime($item->timestamp)));
        $form->select('type_id')->addLabel($this->lang['fee.type_id'])
            ->setSelectOptions($this->feeTypesSelect())
            ->setValue($item->type_id);
        $form->input('sum', [
                'required',
                'readonly',
            ])->addLabel($this->lang['fee.sum'])->setValue($order->cost);
        $form->input('comment', [
                'maxlength' => 500,
            ])->addLabel($this->lang['fee.comment'])->setValue($item->comment);
        $form->button('Сохранить', ['type' => 'submit', 'name' => 'fee_submit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        return $form;
    }
    
    public function feeTypesSelect() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\FeeType', ['active' => 1]);
        $c->select('id, name');
        if ($c->prepare() and $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
    }
    
}