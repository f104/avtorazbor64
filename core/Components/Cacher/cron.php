<?php

namespace Brevis\Components\Cacher;
//use Brevis\Components\Cacher\Cacher as MotorlandAll;
define('PROJECT_API_MODE', true);

$base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
require_once $base . 'index.php';

$process = new \Brevis\Components\Cacher\Cacher($Core);
$process->cacheMarks();
$process->cacheTotalItems();