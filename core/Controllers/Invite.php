<?php

/**
 * Соглашение об обработке персональных данных
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Invite extends Controller {

    public $name = 'Приглашаем к сотрудничеству';
//    public $template = 'invite1';

    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->template = 'invite' . rand(1, 2);
    }
    public function run() {
        return $this->template($this->template);
    }
       
}