<?php

define('PROJECT_API_MODE', true);

$base = dirname(dirname(__FILE__)) . '/';
require_once $base . 'index.php';

$Core->clearCache();

$timezoneOffset = timezone_offset_get( timezone_open( ini_get('date.timezone') ?: 'Europe/Moscow' ), new DateTime() ) / (60*60);

file_put_contents(PROJECT_LM_FILE, gmdate("D, d M Y H:i:s").' GMT+'.$timezoneOffset);