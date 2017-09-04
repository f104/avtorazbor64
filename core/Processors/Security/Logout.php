<?php

/**
 * Отмена авторизации
 *
 * @author kirill
 */

namespace Brevis\Processors\Security;

use Brevis\Processor as Processor;
    
class Logout extends Processor {
    
    public function run() {
        if (!empty($this->core->authUser)) {
            $this->core->authUser->logout();
        }
        $this->success = true;
    }
    
}
    