<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\EventLogger\EventLogger as EventLogger;

class User extends Controller {

    public $name = 'Личный кабинет';
    public $description = 'Личный кабинет пользователя';
    public $template = 'user';
    public $langTopic = 'user';
    public $additionalJs = ['/assets/js/typeahead.jquery.js'];
    
    
    public $fields = [], $errors = [], $content;

    /**
    * @return string
    */
    public function run() {
        $data = [];
        if ($this->core->isAuth) {
            // есть авторизованный пользователь
            switch ($this->core->authUser->getUserGroup()) {
                case 3:
                    // поставщики
                    $data['content'] = $this->supplier();
                    break;
                case 2:
                    // администраторы
                    $data['content'] = $this->admin();
                    break;
            }
        } else {
            // форма авторизации
            $formAuth = new Form([
                'id' => 'auth_form',
            ], $this);
            $formAuth->template = '<form {$options}><fieldset><legend>Авторизация</legend>{$fields} {$buttons} </fieldset></form>';
            $formAuth->input('email', ['type' => 'email', 'required'])->addLabel('E-mail');
            $formAuth->password('givenPassword')
                    ->addLabel('Пароль')
                    ->addHelp('Если вы забыли пароль, оставьте его пустым. Если почта верная, на нее придет новый пароль.');
            $formAuth->checkbox('rememberMe')->addLabel('Запомнить меня');//->setValue(1);
            $formAuth->button('Войти', ['type' => 'submit', 'class' => 'btn btn-primary', 'name' => 'login']);
            if (isset($_REQUEST['login']) and $formAuth->process()) {
                // авторизация
                $fields = $formAuth->getValues();
                if (!empty($fields['givenPassword'])) {
                    // login
                    $processor = $this->core->runProcessor('Security\Login', array_merge($fields, ['confirmUrl' => 'user/confirm']));
                    if ($processor->isSuccess()) {
                        $this->redirect($this->uri);
                    } else {
                        $this->message = $processor->getError();
                    }
                } else {
                    // forgot password
                    $processor = $this->core->runProcessor('Security\Forgot', $fields);
                    if ($processor->isSuccess()) {
                        $this->message = 'Если почта указана верно, на нее придет новый пароль.';
                        $this->success = true;
                    } else {
                        $this->message = $processor->getError();
                    }
                }
            }
            
            // форма регистрации
            $formRegister = new Form([
                'id' => 'register_form',
            ], $this);
            $formRegister->template = ''
                . '<form {$options}>'
                . '<fieldset>'
                . '<legend>Регистрация покупателя</legend>'
                . '{$fields} '
                . '<div class="form-group required">{$buttons}</div> '
                . '</fieldset>'
                . '<br><br><div class="alert alert-info">Если вы хотите стать поставщиком, пожалуйста, <a class="text-nowrap" href="user/registersupplier">пройдите по ссылке</a>.</div>'
                . '</form>';
            $formRegister->input('name', ['required'])->addLabel('Представьтесь, пожалуйста');
            $formRegister->input('email', ['type' => 'email', 'required'])->addLabel('Ваш e-mail');
            $formRegister->password('password', ['required'])->addLabel('Придумайте пароль');
            $formRegister->select('region_id', ['required'])->addLabel('Регион')->setSelectOptions($this->getRegions())->setValue(11);
            $formRegister->input('city', ['required', 'class' => 'form-control js-typeahead-city'])->addLabel('Город')->setValue('Москва');
            $formRegister->input('phone', ['required', 'maxlength' => 50, 'placeholder' => 'Не забудьте указать код города для стационарного телефона'])->addLabel('Телефон');
            $formRegister->checkbox('agree', ['required', 'checked'])->addLabel('Я согласен с условиями <a href="agree" target="_blank" class="mp-ajax-popup-align-top">соглашения об обработке персональных данных</a>');
            $formRegister->button('Отправить', ['type' => 'submit', 'class' => 'btn btn-primary', 'name' => 'register']);
            if (isset($_REQUEST['register']) and $formRegister->process()) {
                // регистрация
                $fields = $formRegister->getValues();
                // проверяем email
                if ($this->core->xpdo->getCount('Brevis\Model\User', ['email' => $fields['email']]) != 0) {
                    $formRegister->addError('email', 'Пользователь с таким email уже зарегистрирован');
                }
                if (!$formRegister->hasError()) {
                    $newUser = $this->core->xpdo->newObject('Brevis\Model\User', $fields);
                    // send activation email
                    $code = $newUser->genHash();
                    $processor = $this->core->runProcessor('Security\SendConfirm', [
                        'name' => $newUser->name,
                        'email' => $newUser->email,
                        'code' => $code,
                        'confirmUrl' => 'user/confirm',
                    ]);
                    if ($processor->isSuccess()) {
                        $newUser->set('hash', $code);
                        $newUser->set('passhash', $fields['password']);
                        $newUser->set('region_id', $fields['region_id']);
                        $newUser->set('city', $fields['city']);
                        $newUser->set('phone', $fields['phone']);
                        $newUser->set('createdon', date('Y-m-d H:i:s',  time()));
                        $newUser->save();
                        // добавляем пользователя в группу
                        $newUser->addUserToGroup(1);
                        $this->success = true;
                        $this->message = 'Мы отправили на указанный email письмо для активации вашей учетной записи. Пожалуйста, проверьте почту.';
                    } else {
                        $this->message = 'sendActivationEmail error';
                    }                
                }
            }
            $data = [
                'formRegister' => $formRegister->draw(),
                'formAuth' => $formAuth->draw(),
            ];
        }
        // отдаем шаблон
        return $this->template($this->template, $data, $this);
    }
    
    private function supplier() {
        $supplier = $this->core->authUser->getOne('UserSupplier');
        $status = $supplier->getOne('Status');
        $data = [
            'supplier' => $supplier->toArray(),
            'status' => $status->toArray(),
            'statuses' => $supplier->getSupplierStatuses(),
            'neworders' => $supplier->getSupplierOrders(1),
        ];
        return $this->template('user.supplier', $data, $this);
    }
    
    private function admin() {
        // собираем статистику
        $moderate = [
                'items' => $this->_countModerateItems(),
                'suppliers' => $this->_countModerateSuppliers(),
                'sklads' => $this->_countModerateSklads(),
            ];
        $data = [
            'moderate' => $moderate,
        ];
        return $this->template('user.admin', $data, $this);
    }
    
    /**
     * Количество товаров, ожидающих модерацию
     * @return int
     */
    private function _countModerateItems() {
        return $this->core->xpdo->getCount('Brevis\Model\Item', ['moderate' => 0]);
    }
    
    /**
     * Количество поставщиков, ожидающих модерацию
     * @return int
     */
    private function _countModerateSuppliers() {
        return $this->core->xpdo->getCount('Brevis\Model\Supplier', ['status_id' => 2]);
    }
    
    /**
     * Количество складов, ожидающих модерацию
     * @return int
     */
    private function _countModerateSklads() {
        return $this->core->xpdo->getCount('Brevis\Model\Sklad', ['status_id' => 2]);
    }
    
}

/**
 * Обновление профиля
 */
class Profile extends User {
    
    /** @TODO использовать Form */
    
    public $name = 'Профиль пользователя';
    public $template = 'user.profile';
    public $permissions = ['is_auth'];
    
    public function run() {
        
        if (!$this->core->isAuth) {
            $this->core->sendErrorPage(403);
        }
                
        if (isset($_REQUEST['update_profile'])) {
            $loggerOld = $this->core->authUser->toArray();
            $this->fields = $this->core->cleanInput($_REQUEST);
            $this->core->authUser->set('name', $this->fields['name']);
            if (!$this->core->authUser->validate()) {
                $validator = $this->core->authUser->getValidator();
                if ($validator->hasMessages()) {
                    foreach ($validator->getMessages() as $message) {
                        //Array ( [field] => email [name] => email [message] => Введите email )
                        $this->errors[$message['field']] = $message['message'];
                    }
                }
            }
            if (!empty($this->fields['newemail']) and $this->fields['newemail'] !== $this->core->authUser->email) {
                if (!preg_match('/^([a-z0-9_\.-]+)@([a-z0-9_\.-]+)$/', $this->fields['newemail'])) {
                    $this->errors['newemail'] = 'Введите корректный e-mail';
                } elseif ($this->core->xpdo->getCount('Brevis\Model\User', ['email' => $this->fields['newemail'], 'id:!=' => $this->core->authUser->get('id')]) != 0) {
                    $this->errors['newemail'] = 'Пользователь с таким e-mail уже зарегистрирован';
                }
            } else {
                unset($this->fields['newemail']);
            }
            
            if (empty($this->fields['city'])) {
                $this->errors['city'] = 'Укажите город';
            }
            if (empty($this->fields['phone'])) {
                $this->errors['phone'] = 'Укажите телефон';
            }
            
            if (empty($this->errors)) {
                $this->core->authUser->set('region_id', $this->fields['region_id']);
                $this->core->authUser->set('city', $this->fields['city']);
                $this->core->authUser->set('phone', $this->fields['phone']);
                if (!empty($this->fields['newPassword'])) {
                    $this->core->authUser->set('passhash', $this->fields['newPassword']);
                }
                $emailChanged = false;
                if (!empty($this->fields['newemail'])) {
                    // send activation email
                    $code = $this->core->authUser->genHash();
                    $processor = $this->core->runProcessor('Security\SendConfirm', [
                        'name' => $this->core->authUser->name,
                        'email' => $this->fields['newemail'],
                        'code' => $code,
                        'confirmUrl' => '/user/confirm',
                    ]);
                    if ($processor->isSuccess()) {
                        $this->core->authUser->set('active', 0);
                        $this->core->authUser->set('email', $this->fields['newemail']);
                        $emailChanged = true;
                    } else {
                        $this->message = 'sendActivationEmail error'; /** @TODO не работает */
                    }     
                    
                }
                $this->core->authUser->save();
                $this->success = true;
                $this->message = 'Изменения сохранены';
                $this->eventLogger = new EventLogger($this->core, $this->loadLexicon('user'), $this->lang['users']);
                $this->eventLogger->langPrefix = 'user.';
                $this->eventLogger->update($this->core->authUser->id, $loggerOld, $this->core->authUser->toArray());                
                if ($emailChanged) {
                    $this->core->authUser->logout();
                    // hash трется при  выходе
                    $this->core->authUser->set('hash', $code);
                    $this->core->authUser->save();
                    $this->redirect('/user');
                }
            }
        } else {
            $this->fields = $this->core->authUser->toArray();
            if ($city = $this->core->authUser->getOne('City')) {
                $this->fields['city'] = $city->get('name');
            }
        }
        
        // отдаем шаблон
        $data = [
            'fields' => $this->fields,
            'errors' => $this->errors,
            'regions' => $this->getRegions(),
            'hauth' => $this->_makeHauthProfileTpl(),
        ];
        return $this->template($this->template, $data, $this);
    }
    
    private function _makeHauthProfileTpl() {
        $hauth = new \Brevis\Components\Hauth\Hauth($this->core);
        $providers = $hauth->getProfileProviders($this->core->authUser->id);
        $tpl = $hauth->getProfileTemplate();
        return $this->template($tpl, [
            'providers' => $providers,
            'url' => 'user/login',
            'return' => urlencode('user/profile'),
        ]);
    }
    
}

/**
 * Подтверждение регистрации
 */
class Confirm extends User {
    
    public $name = 'Подтверждение регистрации';
    public $template = '';
    
    public function run() {
        
        // в случае успеха авторизуем, иначе отдаем 404
        $processor = $this->core->runProcessor('Security\Confirm', $_REQUEST);
        if ($processor->isSuccess()) {
            $this->redirect('user');
        } else {
            $this->core->sendErrorPage();
        }
        
    }

}

/**
 * Авторизация пользователей
 */
class Login extends User {
    public function run() {
        if (isset($_REQUEST['login']) and !empty($this->formAuth) and $this->formAuth->process()) {
            // авторизация
            $fields = $this->formAuth->getValues();
            $redirect = !empty($fields['returnUri']) ? $fields['returnUri'] : $this->makeUrl('parent');
            if (!empty($fields['givenPassword'])) {
                // login
                $processor = $this->core->runProcessor('Security\Login', array_merge($fields, ['confirmUrl' => 'user/confirm']));
                if ($processor->isSuccess()) {
                    $this->success = true;
//                    $this->redirect($redirect);
                } else {
                    $this->message = $processor->getError();
                }
                if ($this->isAjax) {
                    if ($this->success) {
                        $this->core->ajaxResponse(!$this->success, '', ['redirect' => $redirect]);
                    } else {
                        $this->core->ajaxResponse($this->success, $this->message);
                    }
                } else {
                    $this->redirect($redirect);
                }
            } else {
                // forgot password
                $processor = $this->core->runProcessor('Security\Forgot', $fields);
                if ($processor->isSuccess()) {
                    $this->message = 'Если почта указана верно, на нее придет новый пароль.';
                    $this->success = true;
                } else {
                    $this->message = $processor->getError();
                }
                if ($this->isAjax) {
                    $this->core->ajaxResponse($this->success, $this->message);
                } else {
                    return $this->message;
                }
            }
        } elseif (isset($_REQUEST['provider'])) {
            
            // unbind provider
            if ($this->core->isAuth and isset($_REQUEST['action']) and $_REQUEST['action'] == 'unbind') {
                if ($service = $this->core->xpdo->getObject('Brevis\Model\UserHauthService', [
                'user_id' => $this->core->authUser->id, 'provider' => $_REQUEST['provider']
            ])) {
                    $service->remove();
                    $this->redirect($this->makeUrl('user/profile'));
                }
            }
            
            $provider = $_REQUEST['provider'];
            
            try {
                $hauth = new \Brevis\Components\Hauth\Hauth($this->core);
                $hauth->initialize();
                $adapter = $hauth->hybridauth->authenticate( $provider );
                $profile = $adapter->getUserProfile();
            }

            // something went wrong?
            catch( Exception $e ) {
                // переадресуем на авторизацию/регистрацию
                $this->core->log($e->getMessage());
                $this->redirect($this->makeUrl('user'));
            }
            
            // prepare profile
            // convert to array
            $profile = json_decode(json_encode($profile), true);
            $profile = array_change_key_case($profile);
            $profile['provider'] = $provider;
//            var_dump($profile); die;
            // find record
            if (!$service = $this->core->xpdo->getObject('Brevis\Model\UserHauthService', [
                'identifier' => $profile['identifier'], 'provider' => $profile['provider']
            ])) {
                if ($this->core->isAuth) {
                    // create new service for current user
                    $profile['user_id'] = $this->core->authUser->id;
                    $service = $this->core->xpdo->newObject('Brevis\Model\UserHauthService', $profile);
                    $service->save();
                } else {
                    $email = !empty($profile['emailVerified'])
                        ? $profile['emailVerified']
                        : $profile['email'];
                    if (!$user = $this->core->xpdo->getObject('Brevis\Model\User', ['email' => $email])) {
                        // create new user
                        $arr = array(
                            'email' => $email,
                            'name' => !empty($profile['displayname']) ?
                                trim($profile['displayname'])
                                : $email,
                            'active' => 1,
                            'createdon' => date('Y-m-d H:i:s',  time()),
                            'phone' => !empty($profile['phone']) ?
                                trim($profile['phone'])
                                : '',
                        );
                        $user = $this->core->xpdo->newObject('Brevis\Model\User', $arr);
                        $user->set('passhash', $user->generatePassword());
                        $user->save();
                        $user->addUserToGroup(1);
                    }
                    $profile['user_id'] = $user->id;
                    $service = $this->core->xpdo->newObject('Brevis\Model\UserHauthService', $profile);
                    $service->save();
                    $user->login($_REQUEST['remember']);
                }
            } else {
                // update service
                if ($this->core->isAuth) {
                    $profile['user_id'] = $this->core->authUser->id;
                    $service->fromArray($profile);
                    $service->save();
                } else {
                    // find user
                    if ($user = $this->core->xpdo->getObject('Brevis\Model\User', $service->user_id)) {
                        // update service and auth user
                        $service->fromArray($profile);
                        $service->save();
                        $user->login($_REQUEST['remember']);
                    }
                }
            }
            
            $redirect = !empty($_REQUEST['return']) ? urldecode($_REQUEST['return']) : $this->makeUrl('user');
            if ($redirect[0] == '/') {
                $redirect = substr($redirect, 1);
            }
            $this->redirect($redirect);
        }
    }
}

/**
 * Выход из системы
 */
class Logout extends User {
    
    public $name = 'Выход';
    public $uri = 'logout';
    public $template = '';
    
    public function run() {
        $processor = $this->core->runProcessor('Security\Logout');
         if ($processor->isSuccess()) {
            if (!empty($_REQUEST['returnUri'])) {
                header("Location: ".urldecode($_REQUEST['returnUri']));
            } else {
                $this->redirect();
            }
        } else {
            $this->message = $processor->getError();
        }
        
    }

}

class Admin extends User {
    
    public $onlyRedirect = true;
    
    public $permissions = ['admin'];
    
    public function run() {
        $this->redirect('user');
    }
    
}

class Supplier extends User {
    
    public $onlyRedirect = true;
    
    public $permissions = ['supplier']; // проверяем, действительно ли наш пользователь поставщик
    
    public function run() {
        $this->redirect('parent');
    }
    
}

class Info extends Supplier {
    
    public $name = 'Анкета поставщика';
    public $template = '_base';
    public $langTopic = 'supplier';
    public $additionalCSS = ['/assets/css/suggestions.css'];
    public $additionalJs = [
        '/assets/js/jquery.xdomainrequest.min.js',
        '/assets/js/jquery.suggestions.min.js',
        '/assets/js/dadata.js',
    ];

    public function run() {
        if (!$supplier = $this->core->authUser->getOne('UserSupplier')) {
            $this->redirect('user');
        }
        
        $form = new Form([
            'id' => 'supplier_info_form',
            'class' => 'js-ajaxform',
        ], $this);
        
        $form->hidden('id')->setValue($supplier->id)->validate('nochange');
        
        $form->input('inn', [
            'type' => 'text',
            'required',
            'maxlength' => 12,
            'pattern' => '[0-9]{10,12}',
            'title' => '9-12 цифр'
        ])->addLabel($this->lang['supplier.inn'])->setValue($supplier->inn)->addHelp('Начните набирать и выберите из списка.');
        
        $form->input('company_name', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.company_name'])->setValue($supplier->company_name);
        
        $form->input('user_pos', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.your_pos'])->setValue($supplier->user_pos);
        
        $form->select('region_id', ['required'])->addLabel($this->lang['supplier.region_id'])->setSelectOptions($this->getRegions())->setValue($supplier->region_id);
        
        $form->input('city', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
            'class' => 'form-control js-typeahead-city'
        ])->addLabel($this->lang['supplier.city'])->setValue($supplier->getOne('City')->get('name'));
        
        $form->input('index', [
            'type' => 'text',
            'maxlength' => 10,
            'pattern' => '[0-9]{1,10}',
        ])->addLabel($this->lang['supplier.index'])->setValue($supplier->index);
        
        $form->input('legal_address', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.legal_address'])->setValue($supplier->legal_address);
        
        $form->input('actual_address', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.actual_address'])->setValue($supplier->actual_address);
        
        $form->input('dir_name', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.dir_name'])->setValue($supplier->dir_name);
        
        $form->input('buch_name', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.buch_name'])->setValue($supplier->buch_name);
        
        $form->input('email', [
            'type' => 'email',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.email'])->setValue($supplier->email);
        
        $form->input('phone', [
            'type' => 'text',
            'required',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.phone'])->setValue($supplier->phone)->addHelp('Не забудьте указать код города.');
        
        $form->input('fax', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.fax'])->setValue($supplier->fax);
        
        $form->input('kpp', [
            'type' => 'text',
            'maxlength' => 9,
            'pattern' => '[0-9]{1,9}',
            'title' => '9 цифр.'
        ])->addLabel($this->lang['supplier.kpp'])->setValue($supplier->kpp);
        
        $form->input('bik', [
            'type' => 'text',
            'maxlength' => 9,
            'pattern' => '[0-9]{1,9}',
        ])->addLabel($this->lang['supplier.bik'])->setValue($supplier->bik)->addHelp('Начните набирать и выберите из списка.');
        
        $form->input('bank_name', [
            'type' => 'text',
            'maxlength' => 255,
            'required',
        ])->addLabel($this->lang['supplier.bank_name'])->setValue($supplier->bank_name);
        
        $form->input('r_schet', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.r_schet'])->setValue($supplier->r_schet);
        
        $form->input('k_schet', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.k_schet'])->setValue($supplier->k_schet);
        
        $form->input('okved', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.okved'])->setValue($supplier->okved);
        
        $form->input('okpo', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.okpo'])->setValue($supplier->okpo);
        
        $form->input('ogrn', [
            'type' => 'text',
            'required',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.ogrn'])->setValue($supplier->ogrn);
        
        $form->textarea('comment')->addLabel($this->lang['supplier.comment'])->setValue($supplier->comment)->addHelp('Все, что вы посчитаете нужным сообщить дополнительно.');
        
        $form->button('Сохранить', ['type' => 'submit', 'class' => 'btn btn-primary']);
        $form->link('Отмена', ['href' => $this->makeUrl('user'), 'class' => 'btn btn-default']);
        
        if ($form->process()) { 
            if ($form->hasChanged() and !$form->hasError()) {
                $loggerOld = $supplier->toArray();
                $fields = $form->getValues();
                $supplier->fromArray($fields);
                $supplier->set('status_id', 2); // после редактирования меняем статус поставщика
                $supplier->save();
                $loggerNew = $supplier->toArray();
                unset($loggerNew['status_id']);
                $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['suppliers']);
                $this->eventLogger->langPrefix = 'supplier.';
                $this->eventLogger->update($supplier->id, $loggerOld, $loggerNew);
            }
            $this->redirect('user');
        }
        
        if ($this->isAjax) {
            $this->core->ajaxResponse(false, 'Пожалуйста, исправьте ошибки в форме.', ['errors' => $form->getErrors()]);
        }
        
        return $this->template($this->template, ['content' => $form->draw()], $this);
    }
    
}

/**
 * Возвращает список городов для селекта
 */
class Cities extends User {
    public function run() {
        $data = [];
        if (!empty($_REQUEST['country_id']) and !empty($_REQUEST['region_id']) and !empty($_REQUEST['sq'])) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\City', [
                'country_id' => $_REQUEST['country_id'],
                'region_id' => $_REQUEST['region_id'],
                'name:LIKE' => $_REQUEST['sq'].'%',
            ]);
            $c->select('name');
            $c->sortby('name');
            if ($c->prepare() and $c->stmt->execute()) {
                $data = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        die(json_encode($data));
    }
}

class RegisterSupplier extends User {

    public $name = 'Регистрация поставщика';
    public $langTopic = 'user,supplier';
    public $template = '_base';
    public $additionalCSS = ['/assets/css/suggestions.css'];
    public $additionalJs = [
        '/assets/js/jquery.xdomainrequest.min.js',
        '/assets/js/jquery.suggestions.min.js',
        '/assets/js/dadata.js',
    ];

    /**
    * @return string
    */
    public function run() {
        
        if ($this->core->isAuth) {
            $this->core->sendErrorPage(403);
        }
                    
        // форма регистрации
        $form = new Form([
            'id' => 'register_form',
        ], $this);
        $form->input('name', ['required'])->addLabel('Представьтесь, пожалуйста');
        $form->input('email', ['type' => 'email', 'required'])->addLabel('Ваш e-mail');
        $form->password('password', ['required'])->addLabel('Придумайте пароль');
        $form->select('region_id', ['required'])->addLabel('Регион')->setSelectOptions($this->getRegions())->setValue(11);
        $form->input('city', ['required', 'class' => 'form-control js-typeahead-city'])->addLabel('Город')->setValue('Москва');
        $form->input('phone', ['required', 'maxlength' => 50, 'placeholder' => 'Не забудьте указать код города для стационарного телефона'])->addLabel('Телефон');
        
        $form->input('inn', [
            'type' => 'text',
            'required',
            'maxlength' => 12,
            'pattern' => '[0-9]{10,12}',
            'title' => '9-12 цифр'
        ])->addLabel($this->lang['supplier.inn'])->addHelp('Начните набирать и выберите из списка.');
        $form->input('company_name', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.company_name']);
        $form->input('user_pos', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.your_pos']);
        $form->input('index', [
            'type' => 'text',
            'maxlength' => 10,
            'pattern' => '[0-9]{1,10}',
        ])->addLabel($this->lang['supplier.index']);
        $form->input('legal_address', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.legal_address']);
        $form->input('actual_address', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.actual_address']);
        $form->input('dir_name', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.dir_name']);
        $form->input('buch_name', [
            'type' => 'text',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.buch_name']);
        $form->input('company_email', [
            'type' => 'email',
            'required',
            'maxlength' => 255,
        ])->addLabel($this->lang['supplier.email']);
        $form->input('company_phone', [
            'type' => 'text',
            'required',
            'maxlength' => 50,
            'placeholder' => 'Не забудьте указать код города для стационарного телефона',
        ])->addLabel($this->lang['supplier.phone']);
        $form->input('fax', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.fax']);
        $form->input('kpp', [
            'type' => 'text',
            'maxlength' => 9,
            'pattern' => '[0-9]{1,9}',
            'title' => '9 цифр.'
        ])->addLabel($this->lang['supplier.kpp']);
        $form->input('bik', [
            'type' => 'text',
            'maxlength' => 9,
            'pattern' => '[0-9]{1,9}',
        ])->addLabel($this->lang['supplier.bik'])->addHelp('Начните набирать и выберите из списка.');
        $form->input('bank_name', [
            'type' => 'text',
            'maxlength' => 255,
            'required',
        ])->addLabel($this->lang['supplier.bank_name']);
        $form->input('r_schet', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.r_schet']);
        $form->input('k_schet', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.k_schet']);
        $form->input('okved', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.okved']);
        $form->input('okpo', [
            'type' => 'text',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.okpo']);
        $form->input('ogrn', [
            'type' => 'text',
            'required',
            'maxlength' => 50,
        ])->addLabel($this->lang['supplier.ogrn']);
        $form->textarea('comment')->addLabel($this->lang['supplier.comment'])->addHelp('Все, что вы посчитаете нужным сообщить дополнительно.');
        
        $form->checkbox('agree', ['required', 'checked'])->addLabel('Я согласен с условиями <a href="agree" target="_blank" class="mp-ajax-popup-align-top">соглашения об обработке персональных данных</a>');
        $form->button('Отправить', ['type' => 'submit', 'class' => 'btn btn-primary', 'name' => 'register']);
        
        if (isset($_REQUEST['register']) and $form->process()) {
            // регистрация
            $fields = $form->getValues();
            // проверяем email
            if ($this->core->xpdo->getCount('Brevis\Model\User', ['email' => $fields['email']]) != 0) {
                $form->addError('email', 'Пользователь с таким email уже зарегистрирован');
            }
            if (!$form->hasError()) {
                $newUser = $this->core->xpdo->newObject('Brevis\Model\User', $fields);
                // send activation email
                $code = $newUser->genHash();
                $processor = $this->core->runProcessor('Security\SendConfirm', [
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'code' => $code,
                    'confirmUrl' => 'user/confirm',
                ]);
                if ($processor->isSuccess()) {
                    $newUser->set('hash', $code);
                    $newUser->set('passhash', $fields['password']);
                    $newUser->set('region_id', $fields['region_id']);
                    $newUser->set('city', $fields['city']);
                    $newUser->set('phone', $fields['phone']);
                    $newUser->set('createdon', date('Y-m-d H:i:s',  time()));
                    $newUser->save();
                    // добавляем пользователя в группу
                    $newUser->addUserToGroup(3);
                    // поставщик создался при добавлении в группу, обновляем его
                    $fields['name'] = $fields['company_name'];
                    $fields['email'] = $fields['company_email'];
                    $fields['phone'] = $fields['company_phone'];
                    $newSupplier = $newUser->getOne('UserSupplier');
                    $newSupplier->fromArray($fields);
                    $newSupplier->save();
                    $this->success = true;
                    $this->message = 'Мы отправили на указанный email письмо для активации вашей учетной записи. Пожалуйста, проверьте почту.';
                } else {
                    $this->message = 'sendActivationEmail error';
                }                
            }
        }
        
        // отдаем шаблон
        return $this->template($this->template, ['content' => $form->draw()], $this);
    }
    
}