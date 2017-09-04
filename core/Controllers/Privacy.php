<?php

/**
 * Политика конфиденциальности
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Privacy extends Controller {

    public $name = 'Политика конфиденциальности';
    public $template = 'privacy';

    public function run() {
        $html = $this->template($this->template);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $html]);
        }
        return $html;
    }
       
}