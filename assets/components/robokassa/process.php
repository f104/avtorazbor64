<?php

/**
 * Проведение оплаты через Робокассу * 
 */

namespace Brevis\Components\Robokassa;

define('PROJECT_API_MODE', true);

$base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
require_once $base . 'index.php';

$robokassa = new Robokassa($Core);
//echo $robokassa->paymentResult($_REQUEST);
echo $robokassa->rechargeResult($_REQUEST);