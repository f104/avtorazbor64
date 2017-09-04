<?php

/**
 * Базовый класс для компонентов
 */

namespace Brevis\Components;

class Component {
    
    /** @var Core $core */
    public $core;

    /** @var string INFO || ERROR $log_level Уровень логгирования 
     * @default INFO
     */
    public $log_level = 'INFO'; // INFO || ERROR
    /** @var string FILE || MAIL $log_target Место логгирования 
     * @default FILE
     */
    public $log_target = 'FILE';
    /** @var Monolog $logger */
    public $logger;
    
    protected $_error = null;

    function __construct($core, $config = []) {
        $this->core = $core;
        if (!empty($config)) {
            $this->_readConfig($config);
        }
        // logger
        $this->logger = $this->core->newLogger(static::class, $this->log_target, $this->log_level);
    }
    
    private function _readConfig(array $config) {
        $properties = get_class_vars(static::class);
        foreach ($properties as $property => $value) {
            if (isset($config[$property])) {
                $this->$property = $config[$property];
            }
        }
    }

    /**
     * run process
     */
    public function run() {
        return true;
    }
    
    public function hasError() {
        return !empty($this->_error);
    }
    
    public function getError() {
        return $this->_error;
    }
    
    public function addError($msg) {
        $this->_error = $msg;
    }
    
}