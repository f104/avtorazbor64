<?php

    /**
     * Скрипт для крона. Генерация превьюшек для изображений
     */

    namespace Brevis\Components\Tgenerator;
    
    use Brevis\Components\Tgenerator\Tgenerator as Tgenerator;

    define('PROJECT_API_MODE', true);

    $base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
    require_once $base . 'index.php';
    
    $tg = new Tgenerator($Core, [
        'limit' => 50,
//        'log_level' => 'INFO',
//        'log_target' => 'FILE',
    ]);
    
    $tg->run();