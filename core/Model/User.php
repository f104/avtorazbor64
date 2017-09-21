<?php

namespace Brevis\Model;

use xPDO\xPDO;

class User extends \xPDO\Om\xPDOSimpleObject {

    /**
     * The User password field is hashed automatically
     * Создание города при необходимости
     *
     * {@inheritdoc}
    */
    public function set($k, $v= null, $vType= '') {
        if (in_array($k, array('passhash'))) {
            $v = password_hash($v, PASSWORD_DEFAULT);
        }
        if (in_array($k, array('blocked'))) {
            $v = empty($v) ? 0 : 1;
        }
        if ($k == 'city') {
            $city = $this->xpdo->getObject('Brevis\Model\City', [
                'name' => $v, 
                'region_id' => $this->region_id, 
                'country_id' => $this->country_id,
            ]);
            if ($city) {
                if ($city->id != $this->city_id) {
                    $this->set('city_id', $city->id);
                }
            } else {
                $city = $this->xpdo->newObject('Brevis\Model\City', [
                    'name' => $v, 
                    'region_id' => $this->region_id, 
                    'country_id' => $this->country_id,
                ]);
                $city->save();
                $this->set('city_id', $city->id);
            }
        }
        return parent::set($k, $v, $vType);
    }
    
//    public function validate(array $options = array()) {
//        parent::validate($options);
//    }

        /**
     * Автоматическиа генерация пароля
     * @param int $length Длина генерируемого пароля
     * @return string
     */
    public function generatePassword($length = PROJECT_GENERATE_PASSWORD_LENGTH) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
    
    /**
     * Авторизация пользователя
     * @param type $rememberMe
     */
    public function login($rememberMe = null) {
        session_start();
        $_SESSION['uid'] = $this->id;
        $session = $this->xpdo->newObject('Brevis\Model\UserSession', [
            'session_id' => session_id(),
            'user_id' => $this->id,
//            'access' => time(),
        ]);
        $session->save();
        // проверяем "запомнить меня"
        $cookieLifeTime = !empty($rememberMe) ? time() + PROJECT_USER_REMEMBER_TIME : 0;
        // ставим куки
        setcookie('uid', $this->id, $cookieLifeTime, '/');
        setcookie('sid', session_id(), $cookieLifeTime, '/');
    }
    
    /**
     * Деавторизация пользователя
     */
    public function logout() {
        $this->xpdo->removeObject('Brevis\Model\UserSession', ['session_id' => session_id()]);
        session_destroy();
        setcookie('uid', '', time() - 3600, '/');
        setcookie('sid', '', time() - 3600, '/');
    }
    
    /**
     * Генерирует хеш для куки авторизации и для активации
     * @param User $user
     * @return string
     */
    public function genHash() {
        return md5($this->id . $this->email . $this->passhash . time());
    }
    
    /**
     * Gets all the User Group IDs of the groups this user belongs to.
     *
     * @access public
     * @return array An array of User Group IDs.
     */
    public function getUserGroups() {
        $groups = [];
        $id = $this->get('id') ? (string) $this->get('id') : '0';
        if (isset($_SESSION["user.{$id}.groups"])) {
            $groups = $_SESSION["user.{$id}.groups"];
        } else {
//            $memberGroups = $this->xpdo->getCollectionGraph('Group', '{"UserGroupMembers":{}}', array('UserGroupMembers.user_id' => $this->get('id')));
            $memberGroups = $this->getMany('UserGroupMembers');
            if ($memberGroups) {
                /** @var UserGroupMember $group */
                foreach ($memberGroups as $group) $groups[] = $group->get('group_id');
            }
            $_SESSION["user.{$id}.groups"] = $groups;
        }
        return $groups;
    }
    
    /** заглушка, пока группа только одна может быть */
    /**
     * Группа пользователя
     * @param bool $returnObject Если не пусто, вернет объект, а не только ID
     * @return int || Brevis\Model\Group
     */
    
    public function getUserGroup($returnObject = null) {
        $groupID = $this->getUserGroups()[0];
        return empty($returnObject) ? $groupID : $this->xpdo->getObject('Brevis\Model\Group', $groupID);
    }
    
    public function getUserGroupName() {
        $group = $this->xpdo->getObject('Brevis\Model\Group', $this->getUserGroup());
        return $group->name;
    }
    
    /**
     * Gets all the User Permissions Keys of the groups this user belongs to.
     *
     * @access public
     * @return array An array of User Permissions Keys.
     */
    public function getUserPermissions() {
        $groups = $this->getUserGroups();
        $perms = [];
        if (!empty($groups)) {
            $id = $this->get('id') ? (string) $this->get('id') : '0';
            if (isset($_SESSION["user.{$id}.perms"])) {
                $perms = $_SESSION["user.{$id}.perms"];
            } else {
                $GroupPermissions = $this->xpdo->getCollectionGraph('Brevis\Model\Permissions', '{"PermissionGroups":{}}', array('PermissionGroups.group_id:IN' => $groups));
                if ($GroupPermissions) {
                    foreach ($GroupPermissions as $item) $perms[] = $item->get('key');
                }
                // разрешения для поставщика
                if ($groups[0] == 3 and $supplier = $this->getOne('UserSupplier')) {
                    $SupllierPermissions = $this->xpdo->getCollectionGraph('Brevis\Model\Permissions', '{"PermissionSuppliers":{}}', array('PermissionSuppliers.status_id:=' => $supplier->get('status_id')));
                    if ($SupllierPermissions) {
                        foreach ($SupllierPermissions as $item) $perms[] = $item->get('key');
                    }
                }
                $_SESSION["user.{$id}.perms"] = $perms;
            }
        }
        return $perms;
    }
    
    /**
     * Проверяет наличие разрешения (разрешений) у пользователя.
     * Если не выполняется хотя бы одно разрешение, вернет false.
     * 
     * @param string||array $keys Одно (строка) или несколько (массив) разрешений
     * @return boolean
     */
    public function checkPermissions($keys) {
        // администратор может все
        if ($this->getUserGroup() == 2) { return true; }
        if (is_string($keys)) { $keys = [$keys]; }
        $allow = true;
        $perms = $this->getUserPermissions();
        foreach ($keys as $key) {
            if (!in_array($key, $perms)) {
                $allow = false;
                break;
            }
        }
        return $allow;
    }
    
    /**
     * Добавляет пользователя в группу
     * @param int $group
     * @return bool
     */
    public function addUserToGroup($group) {
        $groupMember = $this->xpdo->newObject('Brevis\Model\UserGroupMember', [
            'user_id' => $this->id,
            'group_id' => $group,
        ]);
        // уникальный функционал для групп
        switch ($group) {
            case '3':
                // поставщик, создаем поставщика
                $supplier = $this->xpdo->newObject('Brevis\Model\Supplier', [
                    'user_id' => $this->id,
                    'name' => 'Новый поставщик',
                    'status_id' => 2,
                    'code' => '', // сгенерируется автоматически
                    'country_id' => $this->country_id,
                    'region_id' => $this->region_id,
                    'city_id' => $this->city_id,
                    'ogrn' => $this->ogrn,
                ]);
                $supplier->save();
                break;
        }
        return $groupMember->save();
    }
    
    public function isManager() {
        return in_array($this->getUserGroup(), [2,3,4,5]);
    }
    
    public function isBuyer() {
        return $this->getUserGroup() == 1;
    }
    
    /**
     * Количество неоплаченных заказов и общая сумма к оплате
     * @return false or array [total - кол-во заказов, cost - сумма]
     */
    public function ordersWaitPaiment() {
        $c = $this->xpdo->newQuery('Brevis\Model\Order');
        $c->leftJoin('Brevis\Model\OrderStatus', 'Status');
        $c->select('COUNT(*) AS `total`, SUM(`Order`.`cost`) AS `cost`');
        $c->where([
            'Order.user_id' => $this->id,
            'Status.allow_payment' => 1,
        ]);
        if ($c->prepare() and $c->stmt->execute()) {
            return $c->stmt->fetch(\PDO::FETCH_ASSOC);
        }
        return false;
    }
        
}
    