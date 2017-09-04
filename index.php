<?php

require_once dirname(__FILE__) . '/vendor/autoload.php';
$Core = new \Brevis\Core();

if (!empty($_REQUEST['c'])) {
    $Core->cityKey = $_REQUEST['c'];
    $Core->baseUrl .= $_REQUEST['c'].'/';
    unset($_REQUEST['c']);
}
$req = !empty($_REQUEST['q'])
	? trim($_REQUEST['q'])
	: '';
unset($_REQUEST['q']);

if (!defined('PROJECT_API_MODE') || !PROJECT_API_MODE) {
  $Core->handleRequest($req);
}