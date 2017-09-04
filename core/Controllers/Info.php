<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Info extends Controller {

    public $name = 'Оплата и доставка';
    public $template = 'info';
    
    /**
    * @return string
    */
    public function run() {
        $data = array();
        return $this->template($this->template, $data, $this);
    }
}