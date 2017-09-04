<?php

namespace Brevis\Model;

use xPDO\xPDO;

class Sklad extends \xPDO\Om\xPDOSimpleObject {

    /**
     * Возвращает список статусов
     * @return array
     */
    public function getStatuses() {
        $c = $this->xpdo->newQuery('Brevis\Model\SkladStatus');
        $c->select($this->xpdo->getSelectColumns('Brevis\Model\SkladStatus', 'SkladStatus'));
        $c->sortby('SkladStatus.order','ASC');
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            return $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать SkladStatus:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
    * 
    * {@inheritdoc}
    */
    public function set($k, $v= null, $vType= '') {
        if (in_array($k, array('switchon'))) {
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
        if ($k == 'prefix') {
            $v = strtoupper($v);
        }
        return parent::set($k, $v, $vType);
    }
    
    /**
     * Обновляет время "изменения" склада
     */
    public function updateTime() {
        $this->set('updatedon', date('Y-m-d H:i:s',  time()));
        $this->save();
    }
    
}
