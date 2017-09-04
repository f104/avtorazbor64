<?php

/**
 * Соглашение об обработке персональных данных
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Agree extends Controller {

    public $name = 'Соглашение об обработке персональных данных';
    public $template = 'agree';

    public function run() {
        $html = $this->template($this->template);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $html]);
        }
        return $html;
    }
       
}