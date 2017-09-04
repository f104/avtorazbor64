<?php

namespace Brevis\Components\ImportCatalog;
define('PROJECT_API_MODE', true);

$base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
require_once $base . 'index.php';

$process = new ImportCatalog($Core);
$process->run();