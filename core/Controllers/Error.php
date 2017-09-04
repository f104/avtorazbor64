<?php

/**
 * Страница ошибки
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Error extends Controller {
    public $onlyRedirect = true;
    public function run() {
        $this->redirect();
    }
}
class NotFound extends Error {

    public $name = 'Ошибка 404';
    public $description = 'Запрашиваемая вами страница не найдена.';

    public function run() {
        return $this->template($this->template, ['content' => $this->description]);
    }
    
}
class Forbidden extends Error {

    public $name = 'Ошибка 403';
    public $description = 'Доступ к запрашиваемой вами странице запрещен.';

    public function run() {
        return $this->template($this->template, ['content' => $this->description]);
    }
    
}