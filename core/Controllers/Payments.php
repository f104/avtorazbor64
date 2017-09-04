<?php

/**
 * Управление проплатами
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;
use Brevis\Components\EventLogger\EventLogger as EventLogger;

class Payments extends Controller {

    public $name = 'Платежи';
    public $permissions = ['payments_view'];
    public $langTopic = 'payment';
    
    public $isManager = false; // работает менеджер/админ
    
    /**
     * @var array [id]=> cols
     */
    public $paymentTypes = [];
    public $paymentTypesSelectFilter = []; // селект для фильтров - все типы, даже неактивные
    public $paymentTypesSelectForm = []; // селектов для формы - только активные
    
    public $limit = PROJECT_DEFAULT_QUERY_LIMIT;
    public $page = 1;
    private $_offset = 0;
    private $_total = 0;
    
    public $allowedFilters = ['type_id', 'order_id'];
    public $allowedSort = ['id','timestamp','type_name','order_id', 'sum'];
    public $defaultSort = 'id';
    public $sortdir = 'DESC';
    
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->getPaymentTypes();
        $this->_readFilters();
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['payments'], $this->paymentTypesSelectFilter);
        $this->eventLogger->langPrefix = 'payment.';
    }
    
    /**
     * Получает список типов и пишет их в paymentTypes & paymentTypesSelect
     * @return void
     */
    protected function getPaymentTypes() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\PaymentType');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\PaymentType', 'PaymentType'));
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            $results = [];
            foreach ($rows as $row) {
                $results[$row['id']] = $row;
                $this->paymentTypesSelectFilter[$row['id']] = $row['name'];
                if ($row['active'] == 1) {
                    $this->paymentTypesSelectForm[$row['id']] = $row['name'];
                }
            }
            $this->paymentTypes = $results;
        } else {
            $this->core->log('Не могу выбрать PaymentType:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * список
     */
    public function run() {
        // фильтры
        $filters = new FormFilters([], $this);
        $filters->select('type_id')->addLabel($this->lang['payment.type_id'])->setSelectOptions($this->paymentTypesSelectFilter);
        $filters->input('order_id')->addLabel($this->lang['payment.order_id']);
        $where = [];
        foreach ($this->filters as $k => $v) {
            if ($k != 'page') {
                $where[$k] = $v;
            }
        }
        $c = $this->core->xpdo->newQuery('Brevis\Model\Payment');
        $c->where($where);
        
        $this->_total = $this->core->xpdo->getCount('Brevis\Model\Payment', $c);
        if ($this->page < 1 or $this->page > ceil($this->_total / $this->limit)) {
            $this->page = 1;
            $this->filters['page'] = 1;
        }
        
        $items = [];
        $this->_offset = $this->limit * ($this->page - 1);
        $c->limit($this->limit, $this->_offset);
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Payment', 'Payment'));
        $c->leftJoin('Brevis\Model\PaymentType', 'PaymentType');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\PaymentType', 'PaymentType', 'type_', ['name']));
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать Payment:' . print_r($c->stmt->errorInfo(), true));
        }
        $cols = [];
        $cols['id'] = ['title' => $this->lang['payment.id'], 'tpl' => '@INLINE <a href="payments/view?id={$row.id}&'.http_build_query($this->filters).'">#{$row.id}</a>'];
        $cols['timestamp'] = ['title' => $this->lang['payment.timestamp'], 'tpl' => '@INLINE {$row.timestamp|date:"d-m-Y"}'];
        $cols['type_name'] = ['title' => $this->lang['payment.type_id']];
        if ($this->core->authUser->checkPermissions('orders_view')) {
            $cols['order_id'] = ['title' => $this->lang['payment.order_id'], 'tpl' => '@INLINE <a href="orders/view?id={$row.order_id}">{$row.order_id}</a>'];
        } else {
            $cols['order_id'] = ['title' => $this->lang['payment.order_id']];
        }
        $cols['comment'] = [
            'title' => '',
            'sortable' => false,
            'tpl' => '@INLINE {if $row.comment} <span class="message-in-title" title="{$row.comment|e}"></span>{/if}',
        ];
        $cols['sum'] = [
            'title' => $this->lang['payment.sum'], 
            'class' => 'text-right',
            'tpl' => '@INLINE <span class="text-{$row.sum > 0 ? \'success\' : \'danger\'}">{$row.sum}&nbsp;руб.</span>'
        ];
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $items,
            'total' => $this->_total,
            'totalSum' => $this->_totalSum($where),
            'offset' => $this->_offset,
            'pagination' => $this->getPagination($this->_total, $this->page, $this->limit),
            'addPermission' => 'payments_add',
            'addUrl' => $this->makeUrl($this->uri.'/add', $this->filters),
        ], $this);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $content]);
        }
        $data = [
            'content' => $content,
        ];
        return $this->template($this->template, $data, $this);        
    }
    
    /**
     * SELECT SUM(column)
     * 
     * @param array $where
     * @return int or string if error
     */
    private function _totalSum($where) {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Payment');
        $c->where($where);
        $c->select('SUM(`sum`)');
        if ($c->prepare()) {
            return $this->core->xpdo->getValue($c->stmt);
        } else {
            return print_r($c->stmt->errorInfo(), true);
        }
    }


    /**
     * Форма добавления/изменения платежа
     * @param \Brevis\Model\Payment $payment
     * @return \Brevis\Components\Form
     */
    protected function form(\Brevis\Model\Payment $item) {
        $form = new Form([
            'id' => 'payment_form',
            'class' => 'js-ajaxform',
        ], $this);
        $form->input('timestamp', [
            'required',
            'class' => 'js-datepicker form-control',
        ])->addLabel($this->lang['payment.timestamp'])
          ->setValue($item->timestamp == 'CURRENT_TIMESTAMP' ? date('d-m-Y') : date('d-m-Y', strtotime($item->timestamp)));
        $form->select('type_id')->addLabel($this->lang['payment.type_id'])
            ->setSelectOptions($this->paymentTypesSelectForm)
            ->setValue($item->type_id);
        $form->input('order_id', [
                'required',
                'type' => 'number',
                'min' => 1,
            ])->addLabel($this->lang['payment.order_id'])->setValue($item->order_id);
        $form->input('sum', [
                'required',
                'type' => 'number',
                'min' => 1,
            ])->addLabel($this->lang['payment.sum'])->setValue(abs($item->sum));
        $form->input('comment', [
                'maxlength' => 500,
            ])->addLabel($this->lang['payment.comment'])->setValue($item->comment);
        $form->button('Сохранить', ['type' => 'submit', 'name' => 'payment_submit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        return $form;
    }
    
}

/**
* Новый платеж.
*/
class Add extends Payments {
    
    public $name = 'Ввод платежа';
    public $permissions = ['payments_add'];


    public function run() {
        
        $item = $this->core->xpdo->newObject('Brevis\Model\Payment');
        if (!empty($this->filters['type_id']) and array_key_exists($this->filters['type_id'], $this->paymentTypesSelectForm)) {
            $item->type_id = $this->filters['type_id'];
        }
        if (!empty($this->filters['order_id'])) {
            $item->order_id = $this->filters['order_id'];
        }
        $form = $this->form($item);
        
        if ($form->process()) {
            $fields = $form->getValues();
//            var_dump($fields); die;
            // проверим тип платежа
            if (!array_key_exists($fields['type_id'], $this->paymentTypesSelectForm)) {
                $form->addError('type_id', $this->lang['payment.type_id_incorrect']);
            }
            // проверим заказ
            if (!$order = $this->core->xpdo->getObject('Brevis\Model\Order', ['id' => $fields['order_id']])) {
                $form->addError('order_id', $this->lang['payment.order_id_incorrect']);
            }
            if (!$form->hasError()) {
                // возврат пишем отрицательным числом
                if ($fields['type_id'] == 3) {
                    $fields['sum'] = $fields['sum'] * -1;
                }
                $item->fromArray($fields);
                $item->save();
                $order->checkPaymentStatus($this);
                $this->eventLogger->add($item->id);
                $this->redirect($this->makeUrl('parent', $this->filters));
            } else {
                $this->message = 'Исправьте ошибки в форме.';
            }
        }
        
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
        }
        
        $data = [
            'content' => $form->draw(),
        ];
        return $this->template($this->template, $data, $this);
    }
}

/**
* Редактирование платежа.
*/
class View extends Payments {
    
    public $name = 'Платеж';
    public $permissions = ['payments_add'];

    public function run() {
        
        if (isset($_REQUEST['id']) and $item = $this->core->xpdo->getObject('Brevis\Model\Payment', $_REQUEST['id'])) {
            $itemOld = $item->toArray();
            $this->name .= ' #'.$item->id;
            
            $form = $this->form($item);
        
            if ($form->process()) {
                $fields = $form->getValues();
    //            var_dump($fields); die;
                // проверим тип платежа
                if (!array_key_exists($fields['type_id'], $this->paymentTypesSelectForm)) {
                    $form->addError('type_id', $this->lang['payment.type_id_incorrect']);
                }
                // проверим заказ
                if (!$order = $this->core->xpdo->getObject('Brevis\Model\Order', ['id' => $fields['order_id']])) {
                    $form->addError('order_id', $this->lang['payment.order_id_incorrect']);
                }
                if (!$form->hasError()) {
                    // возврат пишем отрицательным числом
                    if ($fields['type_id'] == 3) {
                        $fields['sum'] = $fields['sum'] * -1;
                    }
                    $item->fromArray($fields);
                    $item->save();
                    $order->checkPaymentStatus($this);
                    $this->eventLogger->update($item->id, $itemOld, $item->toArray());
                    $this->redirect($this->makeUrl('parent', $this->filters));
                } else {
                    $this->message = 'Исправьте ошибки в форме.';
                }
            }

            if ($this->isAjax) {
                $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
            }

            $data = [
                'content' => $form->draw(),
            ];
            return $this->template($this->template, $data, $this);
            
            
        } else {
            $this->core->sendErrorPage();
        }
    }
}