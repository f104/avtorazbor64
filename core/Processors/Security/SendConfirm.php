<?php

/**
 * Обертка для отправки письма подтверждения регистрации
 *
 */

namespace Brevis\Processors\Security;

use Brevis\Processor as Processor;
    
class SendConfirm extends Processor {
    
    public $name;
    public $email;
    public $code;
    public $confirmUrl;
    
    public function run() {
        $processor = $this->core->runProcessor('Mail\Send', [
            'toName' => $this->name,
            'toMail' => $this->email,
            'subject' => 'Активация аккаунта на сайте ' . $this->core->siteDomain,
            'body' => ""
            . "Вы зарегистрировались на сайте {$this->core->siteDomain}, для продолжения работы необходимо подтвердить email.\r\n"
            . "Это вынужденная мера для защиты от спамеров и автоматических регистраций.\r\n"
            . "Для подтверждения откройте ссылку или скопируйте в адресную строку вашего браузера:\r\n"
            . $this->core->siteUrl . '/' . $this->confirmUrl . '?email=' . $this->email . '&code=' . $this->code
            . "\r\n\r\nЕсли вы не регистрировались на сайте {$this->core->siteDomain}, пожалуйста, проигнорируйте это письмо.\r\n"
        ]);
        if ($processor->isSuccess()) {
            $this->success = true;
        } else {
            $this->addError($processor->getError());
        }
    }
    
}
    