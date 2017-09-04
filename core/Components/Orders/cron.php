<?php

    /**
     * Скрипт для ручного запуска проверки
     */
    
    namespace Brevis\Components\Orders;
    
    use Brevis\Components\Orders\Orders as Orders;

    define('PROJECT_API_MODE', true);

    $base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
    require_once $base . 'index.php';
    
    $process = new Orders($Core);
    $process->run();