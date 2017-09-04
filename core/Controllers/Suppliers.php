<?php
    
/**
 * Управление поставщиками
 */

namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;
use Brevis\Components\EventLogger\EventLogger as EventLogger;


class Suppliers extends Controller {

    public $name = 'Поставщики';
    public $permissions = ['suppliers_view'];
    public $langTopic = 'supplier';
    public $allowedFilters = ['status_id'];
    public $allowedSort = ['name','id','user_name'];
    
    public $classname = 'Brevis\Model\Supplier';
    
    public $statuses; // полный список статусов
    public $statusesSelect; // список статусов для селектов
    
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->statuses = $this->getStatuses();
        $this->statusesSelect = $this->getStatusesSelect();
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['suppliers'], $this->statusesSelect);
        $this->eventLogger->langPrefix = 'supplier.';
        $this->_readFilters($_GET);
        $this->exportColumns = ['id', 'name', 'status_name', 'sklad_count', 'user_name'];
    }
    
    public function getRows($raw = false) {
        // посчитаем склады
        $c = $this->core->xpdo->newQuery('Brevis\Model\Sklad');
        $c->select('supplier_id, COUNT(*)');
        $c->groupby('supplier_id');
        if ($c->prepare() && $c->stmt->execute()) {
            $skladCount = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать Sklad:' . print_r($c->stmt->errorInfo(), true));
        }
        $rows = [];
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Supplier'));
        if (!empty($this->filters['status_id'])) {
            $this->where = ['status_id' => $this->filters['status_id']];
        }
        $c->where($this->where);
        $c->leftJoin('Brevis\Model\SupplierStatus', 'Status');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\SupplierStatus', 'Status', 'status_'));
        $c->leftJoin('Brevis\Model\User', 'User');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\User', 'User', 'user_'));
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname .':' . print_r($c->stmt->errorInfo(), true));
        }
        foreach ($rows as &$row) {
            $row['sklad_count'] = isset($skladCount[$row['id']]) ? $skladCount[$row['id']] : 0;
        }
        return $rows;
    }

    /**
     * Список поставщиков
     */
    public function run() {
        // фильтры
        $filters = new FormFilters([], $this);
        $filters->select('status_id')->addLabel($this->lang['supplier.status_id'])->setSelectOptions($this->statusesSelect);
        $cols = [
            'name' => 
                ['title' => $this->lang['supplier.name'], 'tpl' => '@INLINE <a href="'.$this->uri.'/view?id={$row.id}&'.  http_build_query($this->filters).'">{$row.name}</a> <span class="supplier-status-{$row.status_id}">{$row.status_name}</span>'],
//            'code' => 
//                ['title' => $this->lang['supplier.code']],
            'id' => 
                ['title' => $this->lang['supplier.id']],
            'sklad_count' => 
                ['title' => $this->lang['supplier.sklad_count'], 'sortable' => false],
            'user_name' => 
                ['title' => $this->lang['supplier.user_name'], 'tpl' => '@INLINE <a href="/users/view?id={$row.user_id}">{$row.user_name}</a>'],
        ];
        $rows = $this->getRows();
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $rows,
//            'addPermission' => 'suppliers_add',
//            'addUrl' => $this->makeUrl($this->uri.'/add', $this->filters),
        ], $this);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $content]);
        }
        $data = [
            'content' => $content.'<p>Чтобы создать поставщика, создайте пользователя в группе "Поставщики".</p>',
        ];
        return $this->template($this->template, $data, $this);        
    }
    
    /**
     * Возвращает список "свободных" пользователей из группы "поставщики" 
     * плюс пользователь, закрепленный за поставщиком
     * @return array
     */
    protected function _getSupplierUsers($uid = 0) {
        $c = $this->core->xpdo->newQuery('Brevis\Model\User');
        $c->select("User.id, CONCAT_WS(' ', User.email, User.name)");
        $c->sortby('User.name','ASC');
        $c->leftJoin('Brevis\Model\UserGroupMember', 'UserGroupMembers');
        $c->where(['UserGroupMembers.group_id' => $this->suppliersGroupID]);
        $c->leftJoin('Brevis\Model\Supplier', 'UserSupplier');
        $c->where(['UserSupplier.user_id:IS' => null, 'OR:UserSupplier.user_id:=' => $uid]);
//           $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать User:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Возвращает список статусов
     * @return array
     */
    protected function getStatuses() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\SupplierStatus');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\SupplierStatus', 'SupplierStatus'));
        $c->sortby('SupplierStatus.order','ASC');
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать SupplierStatus:' . print_r($c->stmt->errorInfo(), true));
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
    
}

class View extends Suppliers {
    
    public $name = 'Просмотр поставщика';
    public $template = 'suppliers.view';
    public $langTopic = 'supplier,order,payment';
    
    /** @var array ID склады поставщика */
    private $skladsIds = [];
    
    public function run() {
        if (empty($_REQUEST['id']) or !$supplier = $this->core->xpdo->getObject('Brevis\Model\Supplier', ['id' => $_REQUEST['id']])) {
            $this->redirect('parent');
        }
        
        $this->name = $supplier->name;
        $supplierUser = $supplier->getOne('User');
        
        $form = new Form([
            'id' => 'supplier_status_form',
            'class' => 'js-ajaxform',
        ], $this);
        $form->hidden('id')->setValue($supplier->id)->validate('nochange');
        $form->input('name', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.name'])->setValue($supplier->name);
        $form->select('status_id')->addLabel($this->lang['supplier.status_id'])
            ->setSelectOptions($this->statusesSelect)->setValue($supplier->status_id)
            ->addHtml($this->template('_statuses', ['statuses' => $this->statuses]));
        $form->input('status_message', [
            'maxlength' => 500
        ])->addLabel($this->lang['supplier.status_message'])->setValue($supplier->status_message)->addHelp($this->lang['supplier.status_message_help']);
        $form->checkbox('notify', ['checked'])->addLabel($this->lang['supplier.status_notify']);
        $form->button('Сохранить', ['type' => 'submit']);
        $form->link('Отменить', ['href' => $this->makeUrl('parent', $this->filters)]);
                
        if ($form->process()) {
            $fields = $form->getValues();
            if ($form->hasChanged()) {
                $loggerOld = $supplier->toArray();
                // send email
                if (!empty($fields['notify']) and $fields['status_id'] != $supplier->status_id) {
                    $processor = $this->core->runProcessor('Mail\Send', [
                        'toName' => $supplierUser->name,
                        'toMail' => $supplierUser->email,
                        'subject' => 'Смена статуса поставщика на сайте ' . $this->core->siteDomain,
                        'body' => $this->template('mail.manage.suppliers.status', [
                                'name' => $supplierUser->name,
                                'status' => $this->statusesSelect[$fields['status_id']],
                            ], $this),
                    ]);
                    if (!$processor->isSuccess()) {
                        // log
                        $this->core->logger->error('Не получилось отправить письмо с изменением статуса для поставщика '.$supplier->id.': '.$processor->getError());
                    }
                }
                $supplier->fromArray($fields);
                $supplier->save();
                $this->eventLogger->update($supplier->id, $loggerOld, $supplier->toArray());
                $this->success = true;
                $this->message = 'Сохранено';
            }
            
            if ($this->isAjax) {
                $this->core->ajaxResponse(true, $this->message);
            }
        }
        
        if ($this->isAjax) {
            $this->core->ajaxResponse(false, 'Пожалуйста, исправьте ошибки в форме.', ['errors' => $form->getErrors()]);
        }
        
        $supplierArray = $supplier->toArray();
        unset($supplierArray['name'], $supplierArray['code'], $supplierArray['status_id'], $supplierArray['status_message'], $supplierArray['country_id']);
        $supplierArray['user_id'] = $supplierUser->name;
        if ($region = $supplier->getOne('Region')) {
            $supplierArray['region_id'] = $region->name;
        } else {
            $supplierArray['region_id'] = '';            
        }
        if ($city = $supplier->getOne('City')) {
            $supplierArray['city_id'] = $city->name;
        } else {
            $supplierArray['city_id'] = '';
        }
        
        // склады поставщика
        $this->skladsIds = $supplier->getSkladsIds();
        
        $data = [
            'form' => $form->draw(),
            'supplier' => [
                'id' => $supplier->id,
                'skladCount' => count($this->skladsIds),
                'orders' => $this->_getSupplierOrders($supplier),
                'payments' => $this->_getSupplierPayments($supplier),
                'info' => $this->template('_table.info', ['rows' => $supplierArray, 'prefix' => 'supplier.'], $this),
            ],
        ];
        return $this->template($this->template, $data, $this);
    }
    
    private function _getSupplierOrders($supplier) {
        $items = [];
        if (!empty($this->skladsIds)) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\Order');
            $c->where([
                'sklad_id:IN' => $this->skladsIds,
            ]);
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Order', 'Order'));
            $c->leftJoin('Brevis\Model\OrderStatus', 'Status', ('Status.id = Order.status_id'));
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\OrderStatus', 'Status', 'status_', ['name']));
            $c->sortby('Order.createdon', 'DESC');
    //        $c->prepare(); echo $c->toSQL();
            if ($c->prepare() && $c->stmt->execute()) {
                $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $this->core->log('Не могу выбрать Order:' . print_r($c->stmt->errorInfo(), true));
            }
        }
        $cols = [];
        $cols['id'] = ['title' => $this->lang['order.id'], 'tpl' => '@INLINE <a href="orders/view?id={$row.id}">#{$row.id}</a>&nbsp;<span class="order-status-{$row.status_id} js-order-label-{$row.id}">{$row.status_name}</span>'];
        $cols['createdon'] = ['title' => $this->lang['order.createdon'], 'tpl' => '@INLINE {$row.createdon|date:"d-m-Y H:i"}'];
        $cols['item_id'] = ['title' => $this->lang['order.item_id'], 'tpl' => '@INLINE {$row.item_name}'];
        $content = $this->template('_table', [
            'cols' => $cols,
            'rows' => $items,
        ], $this);
        return $content;
    }
    
    private function _getSupplierPayments($supplier) {
        $items = [];
        if (!empty($this->skladsIds)) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\Payment');
            $c->leftJoin('Brevis\Model\Order', 'Order');
            $c->leftJoin('Brevis\Model\PaymentType', 'PaymentType');
            $c->where([
                'Order.sklad_id:IN' => $this->skladsIds,
            ]);
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Payment', 'Payment'));
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\PaymentType', 'PaymentType', 'type_', ['name']));
            $c->sortby('Payment.timestamp', 'DESC');
            $c->sortby('Payment.id', 'DESC');
    //        $c->prepare(); echo $c->toSQL();
            if ($c->prepare() && $c->stmt->execute()) {
                $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            } else {
                $this->core->log('Не могу выбрать Payment:' . print_r($c->stmt->errorInfo(), true));
            }
        }
        // total sum
        $totalSum = 0;
        foreach ($items as $item) {
            $totalSum += $item['sum'];
        }
        $cols = [];
        $cols['timestamp'] = ['title' => $this->lang['payment.timestamp'], 'tpl' => '@INLINE {$row.timestamp|date:"d-m-Y"}'];
        $cols['type_name'] = ['title' => $this->lang['payment.type_id']];
        $cols['order_id'] = ['title' => $this->lang['payment.order_id']];
        $cols['comment'] = [
            'title' => '',
            'tpl' => '@INLINE {if $row.comment} <span class="message-in-title" title="{$row.comment|e}"></span>{/if}',
        ];
        $cols['sum'] = [
                'title' => $this->lang['payment.sum'], 
                'class' => 'text-right',
                'tpl' => '@INLINE <span class="text-{$row.sum > 0 ? \'success\' : \'danger\'}">{$row.sum}&nbsp;руб.</span>'
            ];
        $content = $this->template('_table', [
            'cols' => $cols,
            'rows' => $items,
            'totalSum' => $totalSum,
        ], $this);
        return $content; 
    }
    
}