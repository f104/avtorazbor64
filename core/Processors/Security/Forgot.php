<?php

/**
 * Напоминание пароля
 */

namespace Brevis\Processors\Security;

use Brevis\Processor as Processor;
    
class Forgot extends Processor {
    
    public $email;
    
    public function run() {
        $this->email = $this->core->cleanInput($this->email);
        if (!empty($this->email)) {
            // пытаемся получить пользователя с таким email
            if ($user = $this->core->xpdo->getObject('Brevis\Model\User', ['email' => $this->email])) {
                // ок
                // генерируем новый пароль
                $password = $user->generatePassword();
                // отправляем на почту
                $processor = $this->core->runProcessor('Mail\Send', [
                    'toName' => $user->name,
                    'toMail' => $user->email,
                    'subject' => 'Новый пароль на сайте ' . $this->core->siteDomain,
                    'body' => ""
                    . "Для вас был сгенерирован новый пароль: $password\r\n"
                    . "Если вы не регистрировались на сайте {$this->core->siteDomain}, пожалуйста, проигнорируйте это письмо.\r\n"
                    . $this->core->siteUrl,
                ]);
                if ($processor->isSuccess()) {
                    $user->set('passhash', $password);
                    $user->save();
                    $this->success = true;
                } else {
                    $this->addError($processor->getError());
                }
            } else {
                // сообщаем об успехе в любом случае, чтоб не было возможности проверить регистрацию
                $this->success = true;
            }
        } else {
            $this->addError('Получены пустые данные');
        }
    }
    
    /**
     * Генерирует хеш для куки авторизации
     * @param User $user
     * @return string
     */
    private function _genHash($user) {
        return md5($user->id . $user->email . $user->passhash . time());
    }
    
}
    