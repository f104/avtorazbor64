<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;
use Brevis\Components\EventLogger\EventLogger as EventLogger;

/**
 * Список складов (все или только для данного поставщика)
 */
class Sklads extends Controller {
    
    public $name = 'Склады';
    public $permissions = 'sklads_view';
    public $langTopic = 'sklad';
    public $additionalJs = [
        '/assets/js/sklads.js',
        '/assets/js/typeahead.jquery.js',
    ];
    public $classname = 'Brevis\Model\Sklad';
    public $allowedFilters = ['status_id','switchon','supplier_id'];
    public $allowedSort = ['name','prefix','switchon','updatedon'];
    
    public $statusModerateID = 2; //ID статуса модерации
    public $statusDisabled = [4, 5]; //ID статусов, запрещающих редактирование
    
    /**
     * @var Brevis\Model\Supplier
     */
    public $supplier = null;
    public $suppliers = null; // список поставщиков
    public $isManager = false; // работает менеджер/админ
    public $statuses; // полный список статусов
    public $statusesSelect; // список статусов для селектов
    
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->supplier = $this->core->authUser->getOne('UserSupplier');
        if (empty($this->supplier)) {
            $this->isManager = $this->core->authUser->checkPermissions('sklads_manage');
            $this->allowedSort[] = 'supplier_name';
            $this->suppliers = $this->_getSuppliersFilter();
        }
        $this->statuses = $this->getStatuses();
        $this->statusesSelect = $this->getStatusesSelect();
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['sklads'], $this->statusesSelect);
        $this->eventLogger->langPrefix = 'sklad.';
        $this->_readFilters($_GET);
        // data for export
        if ($this->isManager) {
            $this->exportColumns = ['name', 'prefix', 'status_name', 'switchon', 'supplier_name', 'total_items', 'total_items_show', 'updatedon'];
        }
        if ($this->supplier) {
            $this->exportColumns = ['name', 'prefix', 'status_name', 'switchon', 'total_items', 'total_items_show', 'updatedon'];
        }
    }
    
    public function getRows($raw = false) {
        $rows = [];
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Sklad'));
        if ($this->supplier) {
            $this->where['supplier_id'] = $this->supplier->id;
        } else {
            if (isset($this->filters['supplier_id'])) {
                $this->where['supplier_id'] = $this->filters['supplier_id'];
            }
        }
        if (!empty($this->filters['status_id'])) {
            $this->where['status_id'] = $this->filters['status_id'];
        }
        if (isset($this->filters['switchon'])) {
            $this->where['switchon'] = $this->filters['switchon'];
        }
        $c->leftJoin('Brevis\Model\SkladStatus', 'Status');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\SkladStatus', 'Status', 'status_'));
        if ($this->isManager) {
            // подключаем поставщиков
            $c->leftJoin('Brevis\Model\Supplier', 'Supplier');
            $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Supplier', 'Supplier', 'supplier_'));
        }
        $c->where($this->where);
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            // чтобы зря не считать
            if (!empty($rows)) {
                $totalItems = $this->countItems();
                $totalItemsShow = $this->countItemsShow();
                foreach ($rows as &$row) {
                    $row['total_items'] = isset($totalItems[$row['id']]) ? $totalItems[$row['id']] : 0;
                    $row['total_items_show'] = isset($totalItemsShow[$row['id']]) ? $totalItemsShow[$row['id']] : 0;
                }
            }
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname .':' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    public function run() {
        // фильтры
        $filters = new FormFilters([], $this);
        $filters->select('status_id')->addLabel($this->lang['sklad.status_id'])->setSelectOptions($this->statusesSelect);
        $filters->select('switchon')->addLabel($this->lang['sklad.switchon'])->setSelectOptions([
                1 => $this->lang['yes'],
                0 => $this->lang['no'],
            ]);
        if ($this->suppliers) {
            $filters->select('supplier_id')->addLabel($this->lang['sklad.supplier_id'])->setSelectOptions($this->suppliers);
        }
        $rows = $this->getRows();
        $cols = [
            'name' => 
                ['title' => $this->lang['sklad.name'], 'tpl' => '@INLINE <a href="'.$this->uri.'/view?id={$row.id}&'.  http_build_query($this->filters).'">{$row.name}</a> <span class="sklad-status-{$row.status_id}">{$row.status_name}</span>'],
            'total_items' => 
                ['title' => $this->lang['sklad.total_items'], 'tpl' => '@INLINE {if $row.total_items != 0}<a href="items?sklad_id={$row.id}">{$row.total_items}</a>{else}{$row.total_items}{/if}', 'sortable' => false],
            'total_items_show' => 
                ['title' => $this->lang['sklad.total_items_show'], 'sortable' => false],
            'prefix' => 
                ['title' => $this->lang['sklad.prefix']],
            'switchon' => 
                ['title' => $this->lang['sklad.switchon'], 'tpl' => '@INLINE <input class="js-sklad-switchon-checkbox" data-id="{$row.id}" type="checkbox" {$row.switchon ? "checked" : ""} {if $row.status_id in ['.implode(',', $this->statusDisabled).']}disabled{/if} >'],
        ];
        if ($this->isManager) {
            $cols['supplier_name'] = [
                'title' => $this->lang['sklad.supplier_name'], 
                'tpl' => '@INLINE <a href="/suppliers/view?id={$row.supplier_id}">{$row.supplier_name}</a> [{$row.supplier_id}]'
            ];
        }
        $cols['updatedon'] = [
            'title' => $this->lang['sklad.updatedon'], 
            'tpl' => '@INLINE {if $row.updatedon != "0000-00-00 00:00:00"}{$row.updatedon|date:"d-m-Y H:i"}{/if}'
        ];
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $rows,
            'addPermission' => 'sklads_add',
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
     * Список поставщиков для фильтрации
     * @return array
     */
    private function _getSuppliersFilter() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Supplier');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Supplier', 'Supplier', '', ['id', 'name']));
        $c->innerJoin('Brevis\Model\Sklad', 'Sklad');
        $c->distinct();
        $c->sortby('name');
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать Supplier:' . print_r($c->stmt->errorInfo(), true));
        }
    }

        /**
     * Проверка кода (префикса) на уникальность
     * @param string $prefix
     * @param integer $id Optional
     * @return boolean
     */
    public function prefixAlreadyRegistered($prefix, $id = 0) {
        if ($this->core->xpdo->getObject('Brevis\Model\Sklad', ['id:!=' => $id, 'prefix' => $prefix])) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Список "одобренных" поставщиков
     * @return array
     */
    protected function getSuppliers() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Supplier', ['status_id:='=>3]);
        $c->select("id, name");
        $c->sortby('name','ASC');
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать Supplier:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Возвращает список статусов
     * @return array
     */
    protected function getStatuses() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\SkladStatus');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\SkladStatus', 'SkladStatus'));
        $c->sortby('SkladStatus.order','ASC');
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать SkladStatus:' . print_r($c->stmt->errorInfo(), true));
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
     * Кол-во позиций для складов
     * @return array
     */
    public function countItems() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select(array('sklad_id', 'COUNT(*)'));
        $c->groupby('sklad_id');
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать Items:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    /**
     * Кол-во позиций для складов, которые участвуют в выдаче
     * @return array
     */
    public function countItemsShow() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select(array('sklad_id', 'COUNT(*)'));
        $c->where([
            'published' => 1,
            'moderate' => 1,
            'sklad_id:IN' => $this->core->getSklads(),
        ]);
        $c->groupby('sklad_id');
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать Items:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
}

class Add extends Sklads {
    
    public $name = 'Новый склад';
    public $template = '_base';
    public $permissions = 'sklads_add';
    
    public function run() {
        
        $sklad = $this->core->xpdo->newObject('Brevis\Model\Sklad');
        
        $form = new Form([
            'id' => 'create_sklad_form',
            'class' => 'js-ajaxform',
        ], $this);
        
        if ($this->isManager) {
            $form->select('supplier_id', ['required'])
                ->addLabel($this->lang['sklad.supplier_id'])
                ->addHelp($this->lang['sklad.supplier_id_help'])
                ->setSelectOptions($this->getSuppliers());
        } else {
            $sklad->set('supplier_id', $this->supplier->id);
        }
        
        $form->input('name', [
            'required',
            'maxlength' => 50,
            'autofocus',
        ])->addLabel($this->lang['sklad.name']);
        
        if ($this->isManager) {
            $form->input('prefix', [
                'maxlength' => 4,
                'required',
            ])->addLabel($this->lang['sklad.prefix'])->addHelp($this->lang['sklad.prefix_desc']);
        }
        
        $form->select('region_id', [
            'required',
        ])->addLabel($this->lang['sklad.region_id'])->setSelectOptions($this->getRegions())
          ->setValue($this->supplier ? $this->supplier->region_id : 11);
        
        $form->input('city', [
            'required',
            'class' => 'form-control js-typeahead-city',
        ])->addLabel($this->lang['sklad.city_id'])->setValue($this->supplier ? $this->supplier->getOne('City')->get('name') : '');
        
        $form->input('address', [
            'maxlength' => 255,
        ])->addLabel($this->lang['sklad.address']);
        
        $form->input('additional_emails')
            ->addLabel($this->lang['sklad.additional_emails'])
            ->addHelp($this->lang['sklad.additional_emails_desc']);
        
        $form->checkbox('switchon', ['checked'])->addLabel($this->lang['sklad.switchon'])->addHelp($this->lang['sklad.switchon_help']);
        
        if ($this->isManager) {
            $form->select('status_id')->addLabel($this->lang['sklad.status_id'])
                ->setSelectOptions($this->statusesSelect)->setValue($sklad->status_id)
                ->addHtml($this->template('_statuses', ['statuses' => $this->statuses]));
            $form->input('status_message', [
                'maxlength' => 500
            ])->addLabel($this->lang['sklad.status_message'])->addHelp($this->lang['sklad.status_message_help_manager']);
            $form->checkbox('notify', ['checked'])->addLabel($this->lang['sklad.new_sklad_notify']);
        }
        
        $form->button('Сохранить', ['type' => 'submit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        if ($form->process()) {
            $fields = $form->getValues();
            // проверяем код
            if (isset($fields['prefix']) and $this->prefixAlreadyRegistered($fields['prefix'])) {
                $form->addError('prefix', $this->lang['sklad.prefix_ae']);
            }
            if (!$form->hasError()) {
                if (empty($fields['prefix'])) {
                    // по-умолчанию ставим префикс, сформированный из общего количества складов
                    // менеджер потом поменяет
                    $fields['prefix'] =  sprintf("%'.04d\n", $this->core->xpdo->getCount('Brevis\Model\Sklad') + 1);
                }
                $sklad->fromArray($fields);
                $sklad->save();
                $this->eventLogger->add($sklad->id);
                // send email
                if (!empty($fields['notify'])) {
                    $user = $sklad->getOne('Supplier')->getOne('User');
                    $processor = $this->core->runProcessor('Mail\Send', [
                        'toName' => $user->name,
                        'toMail' => $user->email,
                        'subject' => 'Новый склад на сайте ' . $this->core->siteDomain,
                        'body' => $this->template('mail.manage.sklads.new', [
                                'name' => $user->name,
                                'sklad_name' => $fields['name'],
                            ], $this),
                    ]);
                    if (!$processor->isSuccess()) {
                        $this->core->logger->error('Не получилось отправить письмо о новом складе пользователю '.$user->email.': '.$processor->getError());
                    }
                }
                $this->redirect($this->makeUrl('parent', $this->filters));
            }
        }
        
        if ($this->isAjax) {
            $this->core->ajaxResponse(false, 'Пожалуйста, исправьте ошибки в форме.', ['errors' => $form->getErrors()]);
        }
        
        $data = [
            'content' => $form->draw(),
        ];
        return $this->template($this->template, $data, $this);
    }
}

class View extends Sklads {
    
    public $name = 'Просмотр/редактирование склада';
    public $permissions = 'sklads_add';
    public $template = 'sklads.view';

    public function run() {
        if (empty($_REQUEST['id'])) {
            $this->redirect('parent');
        }
        
        // предупреждение о состоянии статуса
        $statusMessage = null;
        
        $c = $this->core->xpdo->newQuery('Brevis\Model\Sklad');
        $c->where(['id' => $_REQUEST['id']]);
        if ($this->supplier) {
            $c->where(['supplier_id' => $this->supplier->id]);
        }

        if (!$sklad = $this->core->xpdo->getObject('Brevis\Model\Sklad', $c)) {
            $this->redirect('parent');
        }
        
        $this->name = $sklad->name;
        
        if ($this->supplier) {
            if (in_array($sklad->status_id, $this->statusDisabled)) {
                $statusMessage = $this->lang['sklad.status_message_blocked'];
            } else {
                $statusMessage = $this->lang['sklad.status_message_moderate'];
            }
        }
        
        $form = new Form([
            'id' => 'create_sklad_form',
            'class' => 'js-ajaxform',
        ], $this);
        
        $form->hidden('id')->validate('nochange')->setValue($sklad->id);
        
        if ($this->isManager) {
            $form->select('status_id')->addLabel($this->lang['sklad.status_id'])
                ->setSelectOptions($this->statusesSelect)->setValue($sklad->status_id)
                ->addHtml($this->template('_statuses', ['statuses' => $this->statuses]));
            $form->input('status_message', [
                'maxlength' => 500
            ])->addLabel($this->lang['sklad.status_message'])->setValue($sklad->status_message)->addHelp($this->lang['sklad.status_message_help_manager']);
            $form->checkbox('status_notify', ['checked'])->addLabel($this->lang['sklad.status_notify']);
        } else {
            $form->input('status', ['readonly'])
                ->addLabel($this->lang['sklad.status_id'])
                ->setValue($this->statusesSelect[$sklad->status_id])
                ->addHelp($sklad->status_message)
                ->addHtml($this->template('_statuses', ['statuses' => $this->statuses]));
            if (in_array($sklad->status_id, $this->statusDisabled)) {
                // добавляем fieldset disabled
                $form->template = '<form {$options}><fieldset disabled> {$fields} {$buttons} </fieldset></form>';
            }
        }
        
        $form->input('name', [
            'required',
            'maxlength' => 50,
        ])->addLabel($this->lang['sklad.name'])->setValue($sklad->name);
        
        if ($this->isManager) {
            $form->input('prefix', [
                'maxlength' => 4,
                'required',
            ])->addLabel($this->lang['sklad.prefix'])->setValue($sklad->prefix)->addHelp($this->lang['sklad.prefix_desc']);
        }
            
        $form->select('region_id', [
            'required',
        ])->addLabel($this->lang['sklad.region_id'])->setSelectOptions($this->getRegions($sklad->country_id))
          ->setValue($sklad->region_id);
        
        $form->input('city', [
            'required',
            'class' => 'form-control js-typeahead-city',
        ])->addLabel($this->lang['sklad.city_id'])->setValue($sklad->getOne('City')->get('name'));

        $form->input('address', [
            'maxlength' => 255,
        ])->addLabel($this->lang['sklad.address'])->setValue($sklad->address);
        
        $form->input('additional_emails')
            ->addLabel($this->lang['sklad.additional_emails'])
            ->setValue($sklad->additional_emails)
            ->addHelp($this->lang['sklad.additional_emails_desc']);

        $form->checkbox('switchon')->addLabel($this->lang['sklad.switchon'])->addHelp($this->lang['sklad.switchon_help'])->setValue($sklad->switchon);
        
        
        $form->button('Сохранить', ['type' => 'submit', 'name' => 'edit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
                
        if (isset($_REQUEST['edit']) and $form->process()) {
            if (in_array($sklad->status_id, $this->statusDisabled) and !$this->isManager) {
                // нельзя редактировать
                $this->redirect('parent');
            }
            $loggerOld = $sklad->toArray(); // для EventLogger'а
            $fields = $form->getValues();
            // проверяем код
            if (isset($fields['prefix']) and $this->prefixAlreadyRegistered($fields['prefix'], $sklad->id)) {
                $form->addError('prefix', $this->lang['sklad.prefix_ae']);
            }
            if (!$form->hasError()) {
                // send email
                if (!empty($fields['status_notify']) and $sklad->status_id != $fields['status_id']) {
                    $user = $sklad->getOne('Supplier')->getOne('User');
                    $processor = $this->core->runProcessor('Mail\Send', [
                        'toName' => $user->name,
                        'toMail' => $user->email,
                        'subject' => 'Смена статуса склада на сайте ' . $this->core->siteDomain,
                        'body' => $this->template('mail.manage.sklads.status', [
                                'name' => $user->name,
                                'sklad_name' => $fields['name'],
                                'status' => $this->statusesSelect[$fields['status_id']],
                            ], $this),
                    ]);
                    if (!$processor->isSuccess()) {
                        // log
                        $this->core->logger->error('Не получилось отправить письмо с изменением статуса для поставщика '.$user->email.': '.$processor->getError());
                    }
                }
                $form->unsetField('status_notify'); // нужно для правильного срабатывания $form->hasChanged
                if ($form->hasChanged()) {
                    if (!$this->isManager) {
                        $form->unsetField('switchon');
                        if ($form->hasChanged()) {
                            $sklad->set('status_id', $this->statusModerateID);
                        }
                    }
                    $sklad->fromArray($fields);
                    $sklad->save();
                    $this->eventLogger->update($sklad->id, $loggerOld, $sklad->toArray());
                }
                $this->redirect($this->makeUrl('parent', $this->filters));
            }
        }
        
        $data['sklad'] = $sklad->toArray();
        $data['statusMessage'] = $statusMessage;
        $data['formEdit'] = $form->draw();
        $data['totalItems'] = $this->core->xpdo->getCount('Brevis\Model\Item', ['sklad_id' => $sklad->id]);
        $data['totalItemsShow'] = $this->core->xpdo->getCount('Brevis\Model\Item', [
            'sklad_id' => $sklad->id,
            'sklad_id:IN' => $this->core->getSklads(),
            'published' => 1,
            'moderate' => 1,
        ]);
        
        
        // delete
        if ($this->core->authUser->checkPermissions('sklads_manage')) {
            $formDelete = new Form([
                'id' => 'delete_sklad_form',
            ], $this);
            $formDelete->template = '<form {$options}><fielset><legend>'.$this->lang['sklad.delete'].'</legend> {$fields} {$buttons} </fieldset></form>';
            $formDelete->hidden('id')->validate('nochange')->setValue($sklad->id);
            $formDelete->checkbox('confirm_delete', ['required'])->addLabel($this->lang['sklad.confirm_delete'])->addHelp($this->lang['sklad.confirm_delete_desc']);
            $formDelete->button('Удалить', ['type' => 'submit', 'class' => 'btn btn-danger', 'name' => 'delete']);
            $formDelete->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
            
            if (isset($_REQUEST['delete']) and $formDelete->process()) {
                $sklad->remove();
                $this->eventLogger->remove($sklad->id);
                $this->redirect($this->makeUrl('parent', $this->filters));
            }
            
            $data['formDelete'] = $formDelete->draw();
        }
        
        if ($this->isAjax) {
            $this->core->ajaxResponse(false, 'Пожалуйста, исправьте ошибки в форме.', ['errors' => $form->getErrors()]);
        }
        return $this->template($this->template, $data, $this);
    }
    
    
}

/**
 * Вкл / Выкл склада
 */
class Switchon extends Sklads {
    public $permissions = 'sklads_add';
    public function run() {
        if (!empty($_REQUEST['id']) and isset($_REQUEST['switchon'])) { // be 0
            $switchon = empty($_REQUEST['switchon']) ? 0 : 1;
            $where = ['id' => $_REQUEST['id']];
            if ($this->supplier) {
                $where['supplier_id'] = $this->supplier->id;
            }
            if ($sklad = $this->core->xpdo->getObject('Brevis\Model\Sklad', $where)) {
                if ($this->isManager or !in_array($sklad->status_id, $this->statusDisabled)) {
                    $loggerOld = $sklad->toArray();
                    $sklad->set('switchon', $switchon);
                    $sklad->save();
                    $this->success = true;
                    $this->message = 'Сохранено';
                    $this->eventLogger->update($sklad->id, $loggerOld, $sklad->toArray());
               } else {
                    $this->message = 'Вы не можете работать с этим складом';
                }
            }
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            if ($this->success) {
                die($this->message);
            } else {
                $this->core->sendErrorPage();
            }
        }
    }
}