<?php

namespace Brevis\Model;

use xPDO\xPDO;

class Supplier extends \xPDO\Om\xPDOSimpleObject {
    
    public function set($k, $v= null, $vType= '') {
        if ($k == 'code' and empty($v)) {
            /*
             * Берем максимальный ид из базы (свой мы можем не знать, если новая запись).
             * Дополняем нулями.
             * Проверяем, т.к. чисто гипотетически могли ввести такой код руками.
             */
            $c = $this->xpdo->newQuery('Brevis\Model\Supplier');
            $c->select('MAX(id)');
            if ($c->prepare() && $c->stmt->execute()) {
                $id = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
                $id = $id[0];
            } else {
                $this->xpdo->log(xPDO::LOG_LEVEL_ERROR, 'Не могу выбрать Supplier:' . print_r($c->stmt->errorInfo(), true));
            }
            if (empty($id)) { $id = 0; }
            $id++;
            $v = sprintf("%'.08d", $id);
            while ($this->xpdo->getCount('Brevis\Model\Supplier', ['code' => $v])) {
                echo 1;
                $id++;
                $v = sprintf("%'.08d", $id);
            }
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
    
    /**
     * Возвращает список статусов "поставщиков"
     * @return array
     */
    public function getSupplierStatuses() {
        $c = $this->xpdo->newQuery('Brevis\Model\SupplierStatus');
        $c->select($this->xpdo->getSelectColumns('Brevis\Model\SupplierStatus', 'SupplierStatus'));
        $c->sortby('SupplierStatus.order','ASC');
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать SupplierStatus:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Возвращает количество заказов поставщика со статусом $status
     * @var int $status ID
     * @return int
     */
    public function getSupplierOrders($status = null) {
        $className = 'Brevis\Model\Order';
        $c = $this->xpdo->newQuery($className);
        $c->where(['sklad_id:IN' => $this->getSkladsIds()]);
        if (!empty($status)) {
            $c->where(['status_id' => $status]);            
        }
        return $this->xpdo->getCount($className, $c);
    }
    
    /**
     * Возвращает массив с ключами складов "поставщика"
     * @return array
     */
    public function getSkladsIds() {
        $c = $this->xpdo->newQuery('Brevis\Model\Sklad', ['supplier_id' => $this->id]);
        $c->select('id');
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $this->core->log('Не могу выбрать SupplierStatus:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
}