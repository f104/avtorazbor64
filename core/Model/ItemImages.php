<?php

namespace Brevis\Model;

use xPDO\xPDO;

class ItemImages extends \xPDO\Om\xPDOSimpleObject {

    public function remove(array $ancestors = array()) {
        if ($this->filename) {
            @unlink(PROJECT_ASSETS_PATH . 'images/data/' . $this->prefix . '/' . $this->filename);
            @unlink(PROJECT_ASSETS_PATH . 'images/data/' . $this->prefix . '/120x90/' . $this->filename);
        }
        return parent::remove($ancestors);
    }
    
    public function save($cacheFlag = null) {
        if ($this->isNew()) {
            // check and set order
            $order = $this->xpdo->getCount('Brevis\Model\ItemImages', ['item_id' => $this->item_id]);
            $this->set('order', $order);
        }
        return parent::save($cacheFlag);
    }

}
