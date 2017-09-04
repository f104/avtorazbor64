<?php

/**
 * Оферта
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Offer extends Controller {

    public $name = 'Договор-оферта';
    public $template = 'offer';

    public function run() {
        $html = $this->template($this->template);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $html]);
        }
        return $html;
    }
       
}