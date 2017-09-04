<?php

/**
 * Движение средств на балансе. По-умолчанию показываем только свои деньги. Для админа показываем все.
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;
use Brevis\Components\Robokassa\Robokassa as Robokassa;
use Brevis\Components\EventLogger\EventLogger as EventLogger;

class Fees extends Controller {

    public $name = 'Движение средств на балансе';
    public $permissions = ['fees_view'];
    public $langTopic = 'fee';
    
    public $classname = 'Brevis\Model\Fee';
    
    public $isManager = false; // работает менеджер/админ
    
    /**
     * @var array [id]=> cols
     */
    public $feeTypes = [];
    public $feeTypesSelectFilter = []; // селект для фильтров - все типы, даже неактивные
    public $feeTypesSelectForm = []; // селектов для формы - только активные
    
    public $limit = PROJECT_DEFAULT_QUERY_LIMIT;
    public $page = 1;
    private $_offset = 0;
    private $_total = 0;
    
    public $allowedFilters = ['type_id', 'order_id'];
    public $allowedSort = ['user_name','timestamp','type_name','order_id', 'sum'];
    public $defaultSort = 'timestamp';
    public $sortdir = 'DESC';
    
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->getFeeTypes();
        $this->_readFilters($_GET);
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['fees'], $this->feeTypesSelectFilter);
        $this->eventLogger->langPrefix = 'fee.';
        $this->isManager = $core->authUser->isManager();
        if ($this->isManager) {
            $this->allowedFilters[] = 'user_id';
        }
        if ($this->isManager) {
            $this->exportColumns = ['id', 'timestamp', 'user_name', 'type_name', 'sum', 'comment', 'order_id'];
        } else {
            $this->exportColumns = ['id', 'timestamp', 'type_name', 'sum', 'comment', 'order_id'];            
        }
    }
    
    /**
     * Получает список типов и пишет их в paymentTypes & paymentTypesSelect
     * @return void
     */
    protected function getFeeTypes() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\FeeType');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\FeeType', 'FeeType'));
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            $results = [];
            foreach ($rows as $row) {
                $results[$row['id']] = $row;
                $this->feeTypesSelectFilter[$row['id']] = $row['name'];
                if ($row['active'] == 1) {
                    $this->feeTypesSelectForm[$row['id']] = $row['name'];
                }
            }
            $this->feeTypes = $results;
        } else {
            $this->core->log('Не могу выбрать FeeType:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Список пользователей с платежами
     * @return array
     */
    protected function getFeeUsers() {
        $res = [];
        $c = $this->core->xpdo->newQuery('\Brevis\Model\Fee');
        $c->innerJoin('Brevis\Model\User', 'User');
        $c->select(['Fee.user_id', 'User.name', 'User.email']);
        $c->sortby('User.name');
        $c->distinct();
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $res[$row['user_id']] = $row['name'].' ('.$row['email'].')';
            }
        } else {
            $this->core->logger->error('Не могу выбрать EventLog:' . print_r($c->stmt->errorInfo(), true));
        }
        return $res;
    }
    
    public function getRows($raw = false) {
        $items = [];
        if (!$this->isManager) {
            $this->where['user_id'] = $this->core->authUser->id;
        }
        foreach ($this->filters as $k => $v) {
            if ($k != 'page') {
                $this->where[$k] = $v;
            }
        }
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->where($this->where);
        
        $this->_total = $this->core->xpdo->getCount('Brevis\Model\Fee', $c);
        if ($this->page < 1 or $this->page > ceil($this->_total / $this->limit)) {
            $this->page = 1;
            $this->filters['page'] = 1;
        }
        if (!$raw) {
            $this->_offset = $this->limit * ($this->page - 1);
            $c->limit($this->limit, $this->_offset);
        }
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Fee'));
        $c->leftJoin('Brevis\Model\FeeType', 'FeeType');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\FeeType', 'FeeType', 'type_', ['name']));
        $c->leftJoin('Brevis\Model\User', 'User');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\User', 'User', 'user_', ['name', 'email']));
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать Fee:' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /**
     * список
     */
    public function run() {
        // фильтры
        $filters = new FormFilters([], $this);
        if ($this->isManager) {
            $filters->select('user_id')->addLabel($this->lang['fee.user_id'])->setSelectOptions($this->getFeeUsers());
        }
        $filters->select('type_id')->addLabel($this->lang['fee.type_id'])->setSelectOptions($this->feeTypesSelectFilter);
        $filters->input('order_id')->addLabel($this->lang['fee.order_id']);
        $items = $this->getRows();
        $cols = [];
        $cols['timestamp'] = ['title' => $this->lang['fee.timestamp'], 'tpl' => '@INLINE {$row.timestamp|date:"d-m-Y H:i"}'];
        if ($this->isManager) {
            $cols['user_name'] = ['title' => $this->lang['fee.user_id'], 'tpl' => '@INLINE {$row.user_name} ({$row.user_email})'];
            if ($this->core->authUser->checkPermissions('users_view')) {
                $cols['user_name']['tpl'] = '@INLINE <a href="users/view?id={$row.user_id}">{$row.user_name} ({$row.user_email})</a>';
            }
        }
        $cols['type_name'] = ['title' => $this->lang['fee.type_id']];
        $cols['order_id'] = ['title' => $this->lang['fee.order_id'], 'tpl' => '@INLINE {$row.order_id}'];
        if ($this->core->authUser->checkPermissions('orders_view')) {
            $cols['order_id']['tpl'] = '@INLINE <a href="orders/view?id={$row.order_id}">{$row.order_id}</a>';
        }
        $cols['comment'] = ['title' => $this->lang['fee.comment'], 'sortable' => false];
        $cols['sum'] = [
            'title' => $this->lang['fee.sum'], 
            'class' => 'text-right',
            'tpl' => '@INLINE <span class="text-{$row.sum > 0 ? \'success\' : \'danger\'}">{$row.sum}&nbsp;руб.</span>'
        ];
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $items,
            'total' => $this->_total,
            'totalSum' => $this->_totalSum($this->where),
            'offset' => $this->_offset,
            'pagination' => $this->getPagination($this->_total, $this->page, $this->limit),
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
        $c = $this->core->xpdo->newQuery('Brevis\Model\Fee');
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
     * @param \Brevis\Model\Fee $fee
     * @param \Brevis\Model\User $user
     * @param \Brevis\Model\Order $order
     * @return \Brevis\Components\Form
     */
    protected function form(\Brevis\Model\Fee $item, \Brevis\Model\User $user, $order = null) {
        $form = new Form([
            'id' => 'fee_form',
            'class' => 'js-ajaxform',
            'action' => 'fees/add'
        ], $this);
        $form->hidden('user_id', [
            'required',
        ])->setValue($user->id);
        $form->input('user', [
            'readonly',
        ])->addLabel($this->lang['fee.user_id'])->setValue($user->name.' ('.$user->email.')');
        $form->input('timestamp', [
            'required',
            'class' => 'js-datepicker form-control',
        ])->addLabel($this->lang['fee.timestamp'])
          ->setValue($item->timestamp == 'CURRENT_TIMESTAMP' ? date('d-m-Y') : date('d-m-Y', strtotime($item->timestamp)));
        $form->select('type_id')->addLabel($this->lang['fee.type_id'])
            ->setSelectOptions($this->feeTypesSelectForm)
            ->setValue($item->type_id);
        $form->input('sum', [
                'required',
                'type' => 'number',
                'min' => 1,
                'autofocus',
            ])->addLabel($this->lang['fee.sum'])->setValue(abs($item->sum));
        $form->input('comment', [
                'maxlength' => 500,
            ])->addLabel($this->lang['fee.comment'])->setValue($item->comment);
        $form->button('Сохранить', ['type' => 'submit', 'name' => 'fee_submit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        return $form;
    }
    
}

/**
* Пополнение баланса пользователя вручную.
*/
class Add extends Fees {
    
    public $name = 'Пополнение баланса';
    public $permissions = ['fees_add'];


    public function run() {
        
        if (empty($_REQUEST['user_id']) or !$user = $this->core->xpdo->getObject('Brevis\Model\User', $_REQUEST['user_id'])) {
            $this->core->sendErrorPage();
        }
        
        $item = $this->core->xpdo->newObject('Brevis\Model\Fee');
        if (!empty($this->filters['type_id']) and array_key_exists($this->filters['type_id'], $this->feeTypesSelectForm)) {
            $item->type_id = $this->filters['type_id'];
        }
        $form = $this->form($item, $user);
        
        if ($form->process()) {
            $fields = $form->getValues();
//            var_dump($fields); die;
            // проверим тип платежа
            if (!array_key_exists($fields['type_id'], $this->feeTypesSelectForm)) {
                $form->addError('type_id', $this->lang['fee.type_id_incorrect']);
            }
            // проверим пользователя
            if ($fields['user_id'] != $user->id) {
                $form->addError('user', $this->lang['fee.user_id_incorrect']);
            }
            // добавим время?
            if (!$form->hasError()) {
                // списание пишем отрицательным числом
                if ($fields['type_id'] == 4) {
                    $fields['sum'] = $fields['sum'] * -1;
                }
                $item->fromArray($fields);
                $item->save();
                $this->eventLogger->add($item->id);
                $this->success = true;
                $this->message = 'Сохранено';
                
                // уведомление пользователя
                $processor = $this->core->runProcessor('Mail\Send', [
                    'toName' => $user->name,
                    'toMail' => $user->email,
                    'subject' => 'Пополнение баланса',
                    'body' => $this->template('_mail', [
                        'name' => $user->name,
                        'content' => 'Ваш баланс был пополнен на '.$fields['sum'].' руб.',
                        ], $this),
                ]);

                if (!$processor->isSuccess()) {
                    $this->core->logger->error('Не удалось отправить письмо о пополнении баланса'.$item->id);
                }

                
                if ($this->isAjax) {
                    $this->core->ajaxResponse($this->success, $this->message, ['update' => ['.js-userbalance_'.$user->id => $user->balance + $fields['sum']]]);
                }
                $this->redirect($this->makeUrl('parent', $this->filters));
            } else {
                $this->message = 'Исправьте ошибки в форме.';
            }
            
            if ($this->isAjax) {
                $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
            }
        }
        
        $data = [
            'content' => $form->draw(),
        ];
        if ($this->isAjax) {
            $output = $this->template('_ajax', $data, $this);
            $this->core->ajaxResponse(true, null, ['content' => $output]);
        } else {
            return $this->template($this->template, $data, $this);            
        }
    }
}

class Recharge extends Fees {
    
    public function run() {

        if (empty($_REQUEST['OutSum'])) {
            $this->redirect();
        } else {
            $Robo = new Robokassa($this->core);
            $desc = $this->lang['fee.recharge_desc'] . $this->core->siteDomain;
            $sum = $this->core->calculatePayment($_REQUEST['OutSum']);
            $url = $Robo->paymentUrl($sum, $this->core->authUser->id, $this->core->authUser->email, $desc);
            header("Location: ".$url);
            exit();
        }
    }
    
}
