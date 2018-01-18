<?php

/**
 * Управление пользователями
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;
use Brevis\Components\EventLogger\EventLogger as EventLogger;

class Users extends Controller {

    public $name = 'Пользователи';
    public $permissions = ['users_view'];
    public $langTopic = 'user';
    public $additionalJs = ['/assets/js/typeahead.jquery.js'];
    
    public $classname = 'Brevis\Model\User';
    
    public $limit = PROJECT_DEFAULT_QUERY_LIMIT;
    public $page = 1;
    private $_offset = 0;
    private $_total = 0;
    
    public $allowedFilters = ['group_id', 'name', 'email', 'region_id', 'balance'];
    public $allowedSort = ['name','group_name','email','active','blocked','lastlogin','balance'];

    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['users']);
        $this->eventLogger->langPrefix = 'user.';
        $this->_readFilters($_GET);
        $this->exportColumns = ['email', 'name', 'phone', 'group_name', 'active', 'blocked', 'lastlogin', 'balance'];
    }
    
    public function getRows($raw = false) {
        $users = [];
        $c = $this->core->xpdo->newQuery($this->classname, ['id:!=' => $this->core->authUser->id]);
        $c->leftJoin('Brevis\Model\UserGroupMember', 'gm', ('gm.user_id = User.id'));
        $c->leftJoin('Brevis\Model\Group', 'g', ('g.id = gm.group_id'));
        if (!empty($this->filters['group_id'])) {
            $this->where['gm.group_id'] = $this->filters['group_id'];
        }
        if (!empty($this->filters['region_id'])) {
            $this->where['region_id'] = $this->filters['region_id'];
        }
        if (!empty($this->filters['name'])) {
            $this->where['name:LIKE'] = '%'.$this->filters['name'].'%';
        }
        if (!empty($this->filters['email'])) {
            $this->where['email:LIKE'] = '%'.$this->filters['email'].'%';
        }
        if (!empty($this->filters['balance'])) {
            $this->where['balance:!='] = 0;
        }
        $c->where($this->where);
        $this->_total = $this->core->xpdo->getCount('Brevis\Model\User', $c);
        if ($this->page < 1 or $this->page > ceil($this->_total / $this->limit)) {
            $this->page = 1;
            $this->filters['page'] = 1;
        }
        if (!$raw) {
            $this->_offset = $this->limit * ($this->page - 1);
            $c->limit($this->limit, $this->_offset);
        }
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'User'));
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Group', 'g', 'group_', ['name', 'id']));
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $users = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname .':' . print_r($c->stmt->errorInfo(), true));
        }
        return $users;
    }

    /**
     * список пользователей (кроме текущего)
     */
    public function run() {
        // фильтры
        $filters = new FormFilters([], $this);
        $filters->select('group_id')->addLabel($this->lang['user.group'])->setSelectOptions($this->getGroups());
        $filters->input('name', ['placeholder' => $this->lang['filters_like_ph']])->addLabel($this->lang['user.name']);
        $filters->input('email', ['placeholder' => $this->lang['filters_like_ph']])->addLabel($this->lang['user.email']);
        $filters->select('region_id')->addLabel($this->lang['user.region'])->setSelectOptions($this->getRegions(1, true));
        $filters->select('balance')->addLabel($this->lang['user.balance'])->setSelectOptions([1 => 'не нулевой']);
        $cols = [
            'name' => 
                ['title' => $this->lang['user.name'], 'tpl' => '@INLINE <a href="'.$this->uri.'/view?id={$row.id}&'.http_build_query($this->filters).'">{$row.name}</a>'],
            'group_name' => 
                ['title' => $this->lang['user.group_name']],
            'email' => 
                ['title' => $this->lang['user.email'], 'tpl' => '@INLINE <a href="mailto:{$row.email}">{$row.email}</a>'],
            'active' => 
                ['title' => $this->lang['user.active'], 'tpl' => '@INLINE {if $row.active == 1}Да{else}<span class="text-muted">Нет</span>{/if}'],
            'blocked' => 
                ['title' => $this->lang['user.blocked'], 'tpl' => '@INLINE {if $row.blocked == 1}<span class="text-danger">Да</span>{else}Нет{/if}'],
            'lastlogin' => 
                ['title' => $this->lang['user.lastlogin'], 'tpl' => '@INLINE {if $row.lastlogin?}{$row.lastlogin|date:"d-m-Y H:i"}{/if}'],
            'balance' => 
                ['title' => $this->lang['user.balance'], 'class' => 'text-right text-nowrap',
                    'tpl' => '@INLINE {if $row.group_id == 1}<span class="js-userbalance_{$row.id}">{$row.balance}</span>&nbsp;<i class="fa fa-rub"></i> <a href="fees/add?user_id={$row.id}" class="btn btn-primary btn-xs js-ajaxpopup" title="Пополнить"><i class="fa fa-plus"></i></a>{/if}'],
        ];
        $users = $this->getRows();
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $users,
            'total' => $this->_total,
            'offset' => $this->_offset,
            'pagination' => $this->getPagination($this->_total, $this->page, $this->limit),
            'addPermission' => 'users_add',
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
     * Группы пользователей для селекта
     * @return array Groups
     */
    public function getGroups() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Group');
        $c->select('id, name');
        $c->sortby('name','ASC');
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать Group:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Проверка email на повторную регистрацию
     * @param string $email
     * @param integer $id Optional
     * @return boolean
     */
    public function emailAlreadyRegistered($email, $id = 0) {
        if ($this->core->xpdo->getObject('Brevis\Model\User', ['id:!=' => $id, 'email' => $email])) {
            return true;
        } else {
            return false;
        }
    }

}

/**
* Создание пользователя.
*/
class Add extends Users {
    
    public $name = 'Новый пользователь';
    public $permissions = ['users_add'];
    public $template = 'users.view';
    
    public function run() {
        /** @TODO форму в отдельную функцию */
        
        $user = $this->core->xpdo->newObject('Brevis\Model\User');
        $user->set('active', 1);
        
        $form = new Form([
            'id' => 'create_user_form',
            'class' => 'js-ajaxform',
        ], $this);
        
        $form->input('name', [
            'type' => 'text',
            'required',
        ])->addLabel($this->lang['user.name']);
        
        $form->input('email', [
            'type' => 'email',
            'required',
        ])->addLabel($this->lang['user.email']);
        
        $form->input('password', ['required', 'autocomplete' => 'off'])->addLabel($this->lang['user.password']);
        
        $form->select('region_id', ['required'])->addLabel($this->lang['user.region'])->setSelectOptions($this->getRegions())->setValue(11);
        $form->input('city', ['required', 'class' => 'js-typeahead-city form-control'])->addLabel($this->lang['user.city']);
        
        $select = $form->select('group')->addLabel($this->lang['user.group'])->setSelectOptions($this->getGroups());
        if (!empty($this->filters['group_id'])) {
            $select->setValue($this->filters['group_id']);
        }
        
        $form->checkbox('blocked')->addLabel($this->lang['user.blocked']);
        
        $form->button('Сохранить', ['type' => 'submit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        
        if ($form->process()) {
            $fields = $form->getValues();
            $fields['passhash'] = $fields['password'];
            // check exist user 
            if ($this->emailAlreadyRegistered($fields['email'])) {
                $form->addError('email', 'Пользователь с таким email уже зарегистрирован');
            } else {
                $user->fromArray($fields);
                $user->set('createdon', date('Y-m-d H:i:s',  time()));
                
                // send email
                $processor = $this->core->runProcessor('Mail\Send', [
                    'toName' => $user->name,
                    'toMail' => $user->email,
                    'subject' => 'Регистрация на сайте ' . $this->core->siteDomain,
                    'body' => $this->template('mail.manage.users.add', [
                            'name' => $user->name,
                            'email' => $user->email,
                            'password' => $fields['password'],
                        ], $this),
                ]);
                
                if ($processor->isSuccess()) {
                    // save
                    $user->save();
                    $user->addUserToGroup($fields['group']);
                    $this->eventLogger->add($user->id);
                    $this->redirect($this->makeUrl('users/view', array_merge(['id' => $user->id], $this->filters)));                    
                } else {
                    $this->message = $processor->getError();
                }
                
            }
            
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
        }
        
        $data = [
            'user' => $user->toArray(),
            'formEdit' => $form->draw(),
        ];
        return $this->template($this->template, $data, $this);
    }
}

/**
* Манипуляции с пользователем. Просмотр, редактирование, смена пароля, группы.
*/
class View extends Users {
    
    public $name = 'Просмотр/редактирование пользователя';
    public $permissions = ['users_add'];
    public $template = 'users.view';
    
    public $langTopic = 'user,order,fee';

    public function run() {
        
        if (empty($_REQUEST['id']) or !$user = $this->core->xpdo->getObject('Brevis\Model\User', ['id:=' => $_REQUEST['id'], 'id:!=' => $this->core->authUser->id])) {
            $this->redirect('parent');
        }
        
        // запрос во всплывающем окне
        $popup = !empty($_REQUEST['popup']) and $_REQUEST['popup'] == 1;
        
        $this->name = $user->name;
        
        $form = new Form([
            'id' => 'update_user_form',
            'class' => 'js-ajaxform',
        ], $this);
        
        $form->hidden('id')->setValue($user->id)->validate('nochange');
        
        $form->input('name', [
            'type' => 'text',
            'required',
        ])->addLabel($this->lang['user.name'])->setValue($user->name);
        
        $form->input('email', [
            'type' => 'email',
            'required',
        ])->addLabel($this->lang['user.email'])->setValue($user->email);
        
        $form->input('password', ['autocomplete' => 'off'])->addLabel($this->lang['user.new_password'])->addHelp($this->lang['user.new_password_help']);
        
        $form->select('region_id', ['required'])->addLabel($this->lang['user.region'])->setSelectOptions($this->getRegions())->setValue($user->region_id);
        $form->input('city', ['required', 'class' => 'js-typeahead-city form-control'])->addLabel($this->lang['user.city'])->setValue($user->getOne('City')->name);
        $form->input('phone', ['maxlength' => 50, 'placeholder' => 'Не забудьте указать код города для стационарного телефона'])->addLabel($this->lang['user.phone'])->setValue($user->phone);
        
        $userGroup = $user->getUserGroup(true);
        if ($userGroup->nochange == 1) {
            // группу сменить нельзя
            $form->input('group', ['readonly'])
                ->addLabel($this->lang['user.group'])
                ->setValue($userGroup->name)
                ->addHelp($this->lang['user.group_nochange_help']);
        } else {
            $form->select('group')->addLabel($this->lang['user.group'])->setSelectOptions($this->getGroups())->setValue($userGroup->id);
        }
        
        if ($userGroup->id == 1) {
            $form->select('buyer_level')->addLabel($this->lang['user.buyer_level'])->setSelectOptions($this->_getBuyerLevels())->setValue($user->buyer_level);
        }
        
        $form->checkbox('blocked')->addLabel($this->lang['user.blocked'])->setValue($user->blocked);
        
        $form->button('Сохранить', ['type' => 'submit', 'name' => 'edit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        
        if (isset($_REQUEST['edit']) and $form->process()) {
            if ($form->hasChanged()) {
                // update user
                $fields = $form->getValues();
                if (!empty($fields['password'])) {
                    // update password
                    $fields['passhash'] = $fields['password'];
                }
                // check exist user 
                if ($this->emailAlreadyRegistered($fields['email'], $user->id)) {
                    $form->addError('email', $this->lang['user.email_ae']);
                }

                // поставщик

                if (!$form->hasError()) {
                    $loggerOld = $user->toArray();
                    $user->fromArray($fields);
                    if ($userGroup->nochange == 0 and !empty($fields['group']) and $userGroup->id != $fields['group']) {
                        // update user groups
                        $this->core->xpdo->removeCollection('Brevis\Model\UserGroupMember', ['user_id' => $user->id]);
                        $ug = $this->core->xpdo->newObject('Brevis\Model\UserGroupMember', [
                            'user_id' => $user->id, 
                            'group_id' => $fields['group']
                        ]);
                        $user->addMany($ug, 'UserGroupMembers');
                    }
                    // logout user
                    $user->set('hash', '');
                    // save
                    $user->save();
                    $this->eventLogger->update($user->id, $loggerOld, $user->toArray());
                    $this->success = true;
                    $this->message = 'Изменения сохранены.';

//                    if ($this->isAjax) {
//                        $this->core->ajaxResponse($this->success, $this->message);
//                    }
                }
                
            } else {
                $this->success = true;
                $this->message = 'Ничего не изменено.';                
            }
            if ($this->isAjax) {
                $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
            }
        }
        
        // delete
        $formDelete = new Form([
            'id' => 'delete_user_form',
        ], $this);
        $formDelete->template = '<form {$options}><fielset><legend>'.$this->lang['user.delete'].'</legend> {$fields} {$buttons} </fieldset></form>';
        $formDelete->hidden('id')->validate('nochange')->setValue($user->id);
        $formDelete->checkbox('confirm_delete', ['required'])->addLabel($this->lang['user.confirm_delete'])->addHelp($this->lang['user.confirm_delete_desc']);
        $formDelete->button('Удалить', ['type' => 'submit', 'class' => 'btn btn-danger', 'name' => 'delete']);
        $formDelete->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);

        if (isset($_REQUEST['delete']) and $formDelete->process()) {
            $user->remove();
            $this->eventLogger->remove($user->id);
            $this->redirect($this->makeUrl('parent', $this->filters));
        }

        /** @TODO Если есть success & message не вынести ли ajaxResponse в handleRequest? */
        if ($this->isAjax and !$popup) {
            $this->core->ajaxResponse(false, 'Пожалуйста, исправьте ошибки в форме.', ['errors' => $form->getErrors()]);
        }
        
        $data = [
            'user' => $user->toArray(),
            'userGroup' => $userGroup->toArray(),
            'formEdit' => $form->draw(),
            'formDelete' => $formDelete->draw(),
            'popup' => $popup,
        ];
        if ($popup) {
            $data['region'] = $user->getOne('Region')->name;
            $data['city'] = $user->getOne('City')->name;
        }
        // различные данные для различных групп
        switch ($userGroup->id) {
            case 1:
                // покупатели
                $data['user']['orders'] = $this->_getUserOrders($user);
                $data['user']['fees'] = $this->_getUserFees($user);
                break;
        }
        $output = $this->template($this->template, $data, $this);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, null, ['content' => $output]);
        } else {
            return $output;
        }
    }
    
    private function _getUserOrders($user) {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\Order');
        $c->where([
            'user_id' => $user->id,
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
        $cols = [];
        $cols['id'] = ['title' => $this->lang['order.id'], 'tpl' => '@INLINE <a href="orders/view?id={$row.id}">#{$row.id}</a>&nbsp;<span class="order-status-{$row.status_id} js-order-label-{$row.id}">{$row.status_name}</span>', 'sortable' => false];
        $cols['createdon'] = ['title' => $this->lang['order.createdon'], 'tpl' => '@INLINE {$row.createdon|date:"d-m-Y H:i"}', 'sortable' => false];
        $cols['item_id'] = ['title' => $this->lang['order.item_id'], 'tpl' => '@INLINE {$row.item_name}', 'sortable' => false];
        $content = $this->template('_table', [
            'cols' => $cols,
            'rows' => $items,
        ], $this);
        return $content;
    }
    
    private function _getUserFees($user) {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\Fee');
        $c->leftJoin('Brevis\Model\Order', 'Order');
        $c->leftJoin('Brevis\Model\FeeType', 'FeeType');
        $c->where([
            'Fee.user_id' => $user->id,
        ]);
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Fee', 'Fee'));
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\FeeType', 'FeeType', 'type_', ['name']));
        $c->sortby('Fee.timestamp', 'DESC');
        $c->sortby('Fee.id', 'DESC');
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать Fee:' . print_r($c->stmt->errorInfo(), true));
        }
        // total sum
        $totalSum = 0;
        foreach ($items as $item) {
            $totalSum += $item['sum'];
        }
        $cols = [];
        $cols['timestamp'] = ['title' => $this->lang['fee.timestamp'], 'tpl' => '@INLINE {$row.timestamp|date:"d-m-Y"}', 'sortable' => false];
        $cols['type_name'] = ['title' => $this->lang['fee.type_id'], 'sortable' => false];
        $cols['order_id'] = ['title' => $this->lang['fee.order_id'], 'sortable' => false];
        $cols['comment'] = ['title' => $this->lang['fee.comment'], 'sortable' => false];
        $cols['sum'] = [
                'title' => $this->lang['fee.sum'], 
                'class' => 'text-right',
                'tpl' => '@INLINE <span class="text-{$row.sum > 0 ? \'success\' : \'danger\'}">{$row.sum}&nbsp;руб.</span>',
                'sortable' => false,
            ];
        $content = $this->template('_table', [
            'cols' => $cols,
            'rows' => $items,
            'totalSum' => $totalSum,
        ], $this);
        return $content; 
    }
    
    private function _getBuyerLevels() {
        $items = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\BuyerLevel');
        $c->select('id, name');
        if ($c->prepare() and $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        }
        return $items;
    }
}

class BuyerLevels extends Users {
    
    public $name = 'Уровни цен';
//    public $permissions = ['buyerlevels_view'];
    public $classname = 'Brevis\Model\BuyerLevel';
    public $template = 'users.buyerlevels';
    
    public function run() {
        $items = [];
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->select($this->core->xpdo->getSelectColumns($this->classname));
        $c->sortby('increase', 'DESC');
        if ($c->prepare() and $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname .':' . print_r($c->stmt->errorInfo(), true));
        }
//        var_dump($items);
        $content = $this->template($this->template, [
            'items' => $items,
            'newItem' => $this->core->xpdo->newObject($this->classname)->toArray(),
        ], $this);
        return $content; 
    }
    
}

class Update extends BuyerLevels {
    
    public function run() {
        $data = ['update' => true, 'errors' => []];
        if (!empty($_REQUEST['id'])) {
            // exist item
            if ($item = $this->core->xpdo->getObject($this->classname, $_REQUEST['id'])) {
                if (isset($_REQUEST['update'])) {
                    // update
                    if (!empty($name = $this->core->cleanInput($_REQUEST['name'])) and isset($_REQUEST['increase'])) {
                        $increase = intval($_REQUEST['increase']);
                        if (!$ae = $this->core->xpdo->getObject($this->classname, ['id:!=' => $item->id, 'name' => $name])) {
                            if (!$ae = $this->core->xpdo->getObject($this->classname, ['id:!=' => $item->id, 'increase' => $increase])) {
                                $item->set('name', $name);
                                $item->set('increase', $increase);
                                if ($item->save()) {
                                    $this->success = true;
                                    $this->message = $this->lang['saved'];
                                } else {
                                    $this->message = $this->lang['save_db_error'];
                                }
                            } else {
                                $this->message = $data['errors']['increase'] = 'Такая запись уже существует.';
                            }
                        } else {
                            $this->message = $data['errors']['name'] = 'Такая запись уже существует.';
                        }                    
                    } else {
                        $this->message = 'Заполните все поля';
                    }
                }
                if (isset($_REQUEST['remove'])) {
                    // remove
                    if ($item->allow_remove == 1) {
                        $item->remove();
                        $this->message = $this->lang['removed'];
                        $data['removed'] = $this->success = true;
                    } else {
                        $this->message = 'Operation not allowed';
                    }
                }
            } else {
                $this->message('Item not found');
            }
        } else {
            // new
            $item = $this->core->xpdo->newObject($this->classname);
            if (!empty($name = $this->core->cleanInput($_REQUEST['name'])) and !empty($increase = intval($_REQUEST['increase']))) {
                if (!$ae = $this->core->xpdo->getObject($this->classname, ['name' => $name])) {
                    if (!$ae = $this->core->xpdo->getObject($this->classname, ['increase' => $increase])) {
                        $item->set('name', $name);
                        $item->set('increase', $increase);
                        if ($item->save()) {
                            $data['redirect'] = $this->makeUrl('users/buyerlevels');
                        } else {
                            $this->message = $this->lang['save_db_error'];
                        }
                    } else {
                        $this->message = $data['errors']['increase'] = 'Такая запись уже существует.';
                    }
                } else {
                    $this->message = $data['errors']['name'] = 'Такая запись уже существует.';
                }                    
            } else {
                $this->message = 'Заполните все поля';
            }
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, $data);
        } else {
            return $this->message;
        }
    }
    
}