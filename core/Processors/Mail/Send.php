<?php

/**
 * Отправка письма
 *
 */

namespace Brevis\Processors\Mail;

use Brevis\Processor as Processor;
use PHPMailer as PHPMailer;
    
class Send extends Processor {
    
    public $fromName = PROJECT_MAIL_NAME_FROM;
    public $fromMail = PROJECT_MAIL_USERNAME;
    public $toName;
    public $toMail;
    public $replyToName;
    public $replyToMail;
    public $cc;
    public $subject;
    public $body;
    public $isHtml = false;
    
    public $SMTPdebug = false; // отладка phpmailer
    
    public function run() {
        $success = $this->sendMail();
        if ($success === true) {
            $this->success = true;
        } else {
            $this->addError($success);
        }
    }
    
    /**
     * Отправка письма
     * @return true if success or string with error
     */
    private function sendMail() {
        $mail = new PHPMailer;
        
        if ($this->SMTPdebug) {
            $mail->SMTPDebug = 3; // Enable verbose debug output
        }

        $mail->CharSet = "UTF-8";
        $mail->isSMTP();                                // Set mailer to use SMTP
//        $mail->Host = 'smtp.yandex.ru';                // Specify main and backup SMTP servers
        $mail->Host = PROJECT_MAIL_HOST;                // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                         // Enable SMTP authentication
//        $mail->Username = 'noreply@avtoseller.net';        // SMTP username
        $mail->Username = PROJECT_MAIL_USERNAME;        // SMTP username
//        $mail->Password = 'VZ5O9BPzxpFxM8eQdkDP';        // SMTP password
        $mail->Password = PROJECT_MAIL_PASSWORD;        // SMTP password
//        $mail->SMTPSecure = 'ssl';    // Enable TLS encryption, `ssl` also accepted
        $mail->SMTPSecure = PROJECT_MAIL_SMTPSecure;    // Enable TLS encryption, `ssl` also accepted
        $mail->Port = PROJECT_MAIL_PORT;                // TCP port to connect to
        
        $mail->setFrom($this->fromMail, $this->fromName);
        $mail->addAddress($this->toMail);     // Add a recipient
        if (!empty($this->replyToMail) and !empty($this->replyToName)) {
            $mail->addReplyTo($this->replyToMail, $this->replyToName);
        }
        if (!empty($this->cc)) {
            $cc = explode(',', $this->cc);
            foreach ($cc as $item) {
                $mail->addCC($item);
            }
        }

        $mail->isHTML($this->isHtml);                           // Set email format to HTML

        $mail->Subject = $this->subject;
        $mail->Body    = $this->body;

        if(!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return true;
        }
    }
    
}
    