<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Contact extends Controller {

    public $name = 'Контактная информация';
    public $template = 'contact'; // шаблон страницы
    
    /**
    * @return string
    */
    public function run() {
        $data = array();
        return $this->template($this->template, $data, $this);
    }
}