<?php

    namespace Brevis\Components\MonologPHPMailerHandler;

    use Monolog\Handler\AbstractProcessingHandler;
    use PHPMailer as PHPMailer;


    class MonologPHPMailerHandler extends AbstractProcessingHandler {

        private $to;

        public function __construct($to = '', $level = Logger::DEBUG, $bubble = true) {
            parent::__construct($level, $bubble);
            $this->to = $to;
        }

        protected function write(array $record) {
            $mail = new \PHPMailer;

//            $mail->SMTPDebug = 3;

            $mail->isSMTP();
            $mail->Host = PROJECT_MAIL_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = PROJECT_MAIL_USERNAME;
            $mail->Password = PROJECT_MAIL_PASSWORD;
            $mail->SMTPSecure = PROJECT_MAIL_SMTPSecure;
            $mail->Port = PROJECT_MAIL_PORT;

            $mail->setFrom(PROJECT_MAIL_USERNAME, PROJECT_MAIL_NAME_FROM);
            $mail->addAddress($this->to);
            $mail->isHTML(false);

            $mail->Subject = $record['channel'] . '-' . $record['level_name'] . ' ' . $record['message'];
            $mail->Body = $record['formatted'];

            $mail->send();
        }

    }
