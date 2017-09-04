<?php
// Include the composer autoload file
require 'vendor/autoload.php';
// Echo out the response
echo \Munee\Dispatcher::run(new \Munee\Request());