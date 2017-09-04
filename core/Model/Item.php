<?php

namespace Brevis\Model;

use xPDO\xPDO;

class Item extends \xPDO\Om\xPDOSimpleObject {
    public function set($k, $v = null, $vType = '') {
        // из селекта model_key приходит вместе с year_key
        if ($k == 'model_key' and strlen($v) == 5) {
            $this->set('year_key', substr($v, -2));
            $v = substr($v, 0, 3);
        }
        if ($k == 'counter' and strlen($v) != 4) {
            $v = sprintf("%'.04d", $v);
        }
        return parent::set($k, $v, $vType);
    }
    
    public function save($cacheFlag = null) {
        if (empty($this->counter)) {
            $this->set('counter', $this->genCounter());
        }
        if (empty($this->code)) {
            $code = $this->mark_key.$this->model_key.$this->year_key.$this->category_key.$this->element_key.$this->counter;
            $this->set('code', $code);
        }
        if (empty($this->name)) {
            $this->set('name', $this->genName());
        }
        $this->set('updatedon', time());
        return parent::save($cacheFlag);
    }
    
    /**
     * 
     * @param array $criteria where
     * @return array of Brevis\Model\ItemImages
     */
    public function getImages() {
        $c = $this->xpdo->newQuery('Brevis\Model\ItemImages');
        $c->where([
            'filename:IS NOT' => null,
            'OR:url:IS NOT' => null,
        ]);
        $c->sortby('`order`');
        $c->prepare();
        return $this->getMany('Images', $c);
    }
    
     
    /**
     * Счетчик для товара.
     * @return int or false on error
     */
    public function genCounter() {
        $c = $this->xpdo->newQuery($this->_class);
        $c->select('MAX(`counter`)');
        $where = [
            'supplier_id' => $this->supplier_id,
            'mark_key' => $this->mark_key,
            'model_key' => $this->model_key,
            'year_key' => $this->year_key,
            'category_key' => $this->category_key,
            'element_key' => $this->element_key,
        ];
        $c->where($where);
        if ($c->prepare() and $c->stmt->execute()) {
            $res = $c->stmt->fetch(\PDO::FETCH_COLUMN);
            $res++;
            return $res;
        }
        return false;
    }
    
    public function genName() {
        $name = null;
        if ($el = $this->getOne('Element')) {
            $name = $el->name;
            if (!empty($this->mark_key) and !empty($this->model_key) and !empty($this->year_key) and $car = $this->xpdo->getObject('Brevis\Model\Cars', ['mark_key' => $this->mark_key, 'model_key' => $this->model_key, 'year_key' => intval($this->year_key)])) {
                $name .= ' ' . $car->mark_name . ' ' . $car->year_name;
                if (!empty(($this->vendor_code))) {
                    $name .= ' (' . $this->vendor_code . ')';
                }
            }
        }
        return $name;
    }
}
