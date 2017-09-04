<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Page extends Controller {

    public $uri = 'page';
    public $name = 'page';
    
    /**
    * @return string
    */
    public function run() {
        return $this->name;
    }
}

class Subpage extends Page {

    public $uri = 'subpage';
    public $name = 'subpage';
    
    /**
    * @return string
    */
    public function run() {
        return $this->name;
    }
}

class Subsubpage extends Subpage {

    public $uri = 'subsubpage';
    public $name = 'subsubpage';
    
    /**
    * @return string
    */
    public function run() {
        return $this->name;
    }
}