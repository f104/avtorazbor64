<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use PHPMailer as PHPMailer;

class Feedback extends Controller {

    public $name = 'Написать письмо'; // шаблон страницы
    public $template = 'feedback'; // шаблон страницы
    public $mailTemplate = '_feedback.mail'; // шаблон письма
    
    public $SMTPdebug = false; // отладка phpmailer
    
    
    public $fields = array(
        'name' => null, //name
        'email' => null, //email
        'phone' => null, //phone
        'text' => null, //text
    );
    // замена имен полей для антиспама
    public $fieldsSpam = array(
        'name' => 'klfjgdasdfjhweiufs',
        'email' => 'poiwerlkjsdfnmvxzsdf',
        'phone' => 'ouikjhsmnnbvytuoertkjh',
        'text' => 'jklashfafouiyqwerkhjg',
    );
    // правила валидации
    public $validateRules = array(
        'name' => 'required',
        'email' => 'required,email',
        'text' => 'required',
    );
    // сообщения об ошибках валидации
    public $errorsMsg = array(
        'required' => 'Это обязательное поле',
        'email' => 'Введите корректный e-mail',
    );
    
    private $errors = array(); // массив с ошибками
    

    /**
    * @return string
    */
    public function run() {

        if (!isset($_REQUEST['success'])) {
            if ($this->getInput($_REQUEST)) {
                // чистим
                array_walk($this->fields, array($this, 'clearInput'));
                // validate
                if ($this->validate() and $this->sendMail() === true) {
                    // перенаправляем
                    $this->redirect($this->uri.'?success');
                } elseif ($this->isAjax) {
                    $errors = [];
                    foreach ($this->errors as $k => $v) {
                        $errors[$this->fieldsSpam[$k]] = $v;
                    }
                    $this->core->ajaxResponse(false, 'Пожалуйста, исправьте ошибки', ['errors' => $errors]);
                }
            }            
        } else {
            // после редиректа
            $this->success = true;
        }
        // отдаем шаблон
        $data = array(
            'fields' => $this->fields,
            'errors' => $this->errors,
            'success' => $this->success,
        );
        return $this->template($this->template, $data, $this);
    }
    
    /**
     * Читает request и записывает в $this->fields
     * @param request $request
     * @return boolean
     */
    private function getInput($request) {
        $input = false; // есть нужный ввод или нет
        if (!empty($request)) {
            foreach ($this->fields as $key => $val) {
                if (isset($request[$this->fieldsSpam[$key]])) {
                    $this->fields[$key] = $request[$this->fieldsSpam[$key]];
                    $input = true;
                }
            }
        }
        return $input;
    }
    
    /**
     * Чистит пользовательский ввод
     * @param string $value
     */
    private function clearInput(&$value) {
        $value = trim(strip_tags($value));
    }
    
    /**
     * Проверка пользовательского ввода
     * @return boolean
     */
    private function validate() {
        $validated = false;
        foreach ($this->fields as $key => $value) {
            if (array_key_exists($key, $this->validateRules)) {
                $rules = explode(',', $this->validateRules[$key]);
                foreach ($rules as $rule) {
                    if (!$this->validateInput($value, $rule)) {
                        $this->errors[$key] = $this->errorsMsg[$rule];
                        break;
                    }
                }
            }
        }
        if (empty($this->errors)) {
            $validated = true;
        }
        return $validated;
    }
    
    /**
     * Проверка одного поля
     * @param string $value Пользовательский ввод
     * @param string $rule Правило
     * @return boolean
     */
    private function validateInput($value, $rule) {
        $validated = false;
        switch ($rule) {
            case 'required':
                if (strlen($value) != 0) { $validated = true; }
                break;
            case 'email':
                if ($pos = strpos($value, '@') and $pos != strlen($value)-1) { $validated = true; }
                break;
        }
        return $validated;
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
        
        $mail->setFrom(PROJECT_MAIL_USERNAME, PROJECT_MAIL_NAME_FROM);
        $mail->addAddress(PROJECT_MAIL_FEEDBACK_TO);     // Add a recipient
        $mail->addReplyTo($this->fields['email'], $this->fields['name']);

        $mail->isHTML(false);                           // Set email format to HTML

        $mail->Subject = PROJECT_MAIL_SUBJECT;
        $mail->Body    = $this->makeBody();

        if(!$mail->send()) {
            return $mail->ErrorInfo;
        } else {
            return true;
        }
    }
    
    /**
     * Парсит шаблон с письмом
     * @return string
     */
    private function makeBody() {
        $data = array(
            'fields' => $this->fields,
            'date' => date('d-m-Y H:i'),
        );
        return $this->template($this->mailTemplate, $data, $this);
    }
}