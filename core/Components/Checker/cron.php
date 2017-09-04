<?php

    /**
     * Скрипт для ручного запуска проверки
     */
    
    namespace Brevis\Components\Checker;
    
//    use Brevis\Components\Checker\Checker as Checker;

    define('PROJECT_API_MODE', true);

    $base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
    require_once $base . 'index.php';
    
    $process = new Checker($Core);
    $process->run();