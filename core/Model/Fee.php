<?php

namespace Brevis\Model;

use xPDO\xPDO;

class Fee extends \xPDO\Om\xPDOSimpleObject {
    
    public function save($cacheFlag = null) {
        parent::save($cacheFlag);
        // пересчитываем баланс пользователя
        $user = $this->getOne('User');
        $userBalance = $this->_userBalance($user);
        if ($userBalance !== false) {
            $user->set('balance', $userBalance);
            $user->save();
        }
    }
    
    public function remove(array $ancestors = array()) {
        parent::remove($ancestors);
        // пересчитываем баланс пользователя
        $user = $this->getOne('User');
        $userBalance = $this->_userBalance($user);
        if ($userBalance !== false) {
            $user->set('balance', $userBalance);
            $user->save();
        }
    }

    private function _userBalance(\Brevis\Model\User $user) {
        $c = $this->xpdo->newQuery($this->_class);
        $c->where([
            'user_id' => $user->id,
        ]);
        $c->select('SUM(`sum`)');
        if ($c->prepare() and $c->stmt->execute()) {
            return $balance = $c->stmt->fetch(\PDO::FETCH_COLUMN);
        }
        return false;
    }


}