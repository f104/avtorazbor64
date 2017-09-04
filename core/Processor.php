<?php
    
    /**
     * Базовый класс для процессоров
     */

    namespace Brevis;

    use \Exception as Exception;

    class Processor {

        /** @var Core $core */
        public $core;
        public $success = false;
        public $error = '';

        /**
         * Конструктор класса, требует передачи Core
         *
         * @param Core $core
         */
        function __construct(Core $core) {
            $this->core = $core;
        }

        /**
         * Инициализация класса, передача параметров
         * @param array $params
         * @return bool
         */
        public function initialize(array $params = array()) {
            $properties = get_class_vars(static::class);
//            $properties = get_class_vars(get_called_class());
            foreach ($properties as $property => $value) {
                if (isset($params[$property])) {
                    $this->$property = $params[$property];
                }
            }
            return true;
        }
        
        /**
         * Основной процесс
         * 
         */
        public function run() {
            
        }
        
        /**
         * Возвращает true при успешном выполнении
         * @return bool
         */
        public function isSuccess() {
            return $this->success;
        }
        
        /**
         * Добавляет ошибку
         * @param string $message
         */
        public function addError($message = '') {
            $this->error = $message;
        }
        
        /**
         * Возвращает ошибку
         * @return string
         */
        public function getError() {
            return $this->error;
        }
        
    }    