<?php

/**
 * Проверка авторизации
 *
 * @author kirill
 */

namespace Brevis\Processors\Security;

use Brevis\Processor as Processor;
    
class Check extends Processor {
    
    public function run() {
        if (!empty($_COOKIE['uid']) and !empty($_COOKIE['sid'])) {
            session_id($_COOKIE['sid']);
            session_start();
            $auth = false;
            if (!empty($_SESSION['uid']) and $_SESSION['uid'] == $_COOKIE['uid']) {
                // был авторизован в этой сессии
                $auth = true;
            } else {
                $session = $this->core->xpdo->getObject('Brevis\Model\UserSession', [
                    'session_id' => $_COOKIE['sid'],
                    'user_id' => $_COOKIE['uid'],
                ]);
                if ($session) {
                    $auth = true;
                    $_SESSION['uid'] = $_COOKIE['uid'];
                    $session->set('access', time());
                    $session->save();
                }
            }
            if ($auth and $user = $this->core->xpdo->getObject('Brevis\Model\User', $_COOKIE['uid'])) {
                $this->core->authUser = $user;
                $this->core->isAuth = true;
                $this->success = true;
                $user->set('lastlogin', date('Y-m-d H:i:s',  time()));
                $user->save();
                $this->_gc();
            }
        }
    }
    
    private function _gc() {
        $this->core->xpdo->removeCollection('Brevis\Model\UserSession', [
            'access:<' => date('Y-m-d H:i:s',  time() - PROJECT_USER_REMEMBER_TIME)
        ]);
    }
}
    