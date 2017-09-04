<?php

/**
 * Подтверждение регистрации пользователя
 * 
 */

namespace Brevis\Processors\Security;

use Brevis\Processor as Processor;
use Brevis\Controller as Controller;
    
class Confirm extends Processor {
    
    public $email;
    public $code;
    
    public function run() {
        if (!empty($this->email) and !empty($this->code)) {
            // пытаемся получить пользователя
            if ($user = $this->core->xpdo->getObject('Brevis\Model\User', ['email' => $this->email, 'hash' => $this->code])) {
                // ок
                // проверяем активацию
                if ($user->active == 0) {
                    // активируем
                    $user->set('active', 1);
                    $user->set('hash', '');
                    $user->save();
                    // авторизуем
                    $user->login();
                    // уведомляем о новом пользователе
                    $controller = new Controller($this->core);
                    $controller->lang = $controller->loadLexicon('user');
                    $country = $this->core->xpdo->getObject('Brevis\Model\Country', $user->country_id);
                    $region = $this->core->xpdo->getObject('Brevis\Model\Region', $user->region_id);
                    $city = $this->core->xpdo->getObject('Brevis\Model\City', $user->city_id);
                    $processor = $this->core->runProcessor('Mail\Send', [
                        'toMail' => PROJECT_MAIL_ADDRESS_TO,
                        'subject' => 'Уведомление о новом пользователе',
                        'body' => $controller->template('mail.notification.users.new', [
                            'prefix' => 'user.',
                            'fields' => [
                                'name' => $user->name,
                                'email' => $user->email,
                                'phone' => $user->phone,
                                'country_id' => $country->name,
                                'region_id' => $region->name,
                                'city_id' => $city->name,
                            ],
                        ]),
                    ]);
                    if (!$processor->isSuccess()) {
                        $this->core->logger->error('Не удалось отправить письмо с уведомлением о новом пользователе');
                    }
                    // перенаправляем
                    $this->success = true;
                }
            }
        }
    }
    
}
    