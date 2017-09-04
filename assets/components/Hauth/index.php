<?php

$base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
require  $base . 'vendor/hybridauth/hybridauth/hybridauth/Hybrid/Auth.php';
require  $base . 'vendor/hybridauth/hybridauth/hybridauth/Hybrid/Endpoint.php';
\Hybrid_Endpoint::process();