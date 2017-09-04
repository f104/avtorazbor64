<?php

/**
 * Авторизация пользователя
 *
 */

namespace Brevis\Processors\Security;

use Brevis\Processor as Processor;
    
class Login extends Processor {
    
    public $email;
    public $givenPassword;
    public $rememberMe;
    public $confirmUrl;
    
    public function run() {
        $this->email = $this->core->cleanInput($this->email);
        $this->givenPassword = $this->core->cleanInput($this->givenPassword);
        if (!empty($this->email) and !empty($this->givenPassword)) {
            // пытаемся получить пользователя с таким email и паролем
            if ($user = $this->core->xpdo->getObject('Brevis\Model\User', ['email' => $this->email]) and password_verify($this->givenPassword, $user->passhash)) {
                // ок
                // проверяем активацию
                if ($user->active == 0) {
                    // отправляем письмо
                    $code = $user->genHash();
                    $processor = $this->core->runProcessor('Security\SendConfirm', [
                        'name' => $user->name,
                        'email' => $user->email,
                        'code' => $code,
                        'confirmUrl' => $this->confirmUrl,
                    ]);
                    if ($processor->isSuccess()) {
                        $user->set('hash', $code);
                        $user->save();
                        $this->addError('Вы не подтвердили свой email. Пожалуйста, проверьте почту.');
                    } else {
                        $this->addError('sendActivationEmail error');
                    }
                } elseif ($user->blocked == 1) {
                    $this->addError('Ваш аккаунт заблокирован. Обратитесь к администратору.');
                } else {
                    // авторизуем
                    $user->login($this->rememberMe);
                    $this->success = true;
                }                
            } else {
                $this->addError('Некорректная пара логин/пароль');
            }
        } else {
            $this->addError('Получены пустые данные');
        }
    }
    
}
    