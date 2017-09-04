<?php

namespace Brevis\Components\Cacher;

use Brevis\Components\Component as Component;

class Cacher extends Component {
    
    public $where = [
        'Item.published' => 1,
        'Item.moderate' => 1,
        'Item.error:IS' => null,
    ];
    
    public function __construct($core, $config = []) {
        parent::__construct($core, $config);
        $this->_sklads = $this->core->getSklads();
        if (!empty($this->_sklads)) {
            $this->where['Item.sklad_id:IN'] = $this->_sklads;
        } else {
            // заведомо ложное условие, все склады выключены или поставщики заблокированы
            $this->where['Item.id'] = 0;
        }
    }
    
    public function cacheMarks() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Cars', 'Cars', '', array('mark_key','mark_name')));
        $c->distinct();
        $c->sortby('mark_name', 'ASC');
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $k => $row) {
                $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
                $where = $this->where;
                $where['mark_key'] = $row['mark_key'];
                $c->where($where);
                $count = $this->core->xpdo->getCount('Brevis\Model\Item', $c);
                if ($count == 0) {
                    unset ($rows[$k]);
                }
            }
        } else {
            $this->core->logger->error('Не могу выбрать marks:' . print_r($c->stmt->errorInfo(), true));
        }
        $this->core->cacheManager->set('cars.marks', $rows, $this->core->cacheLifetime);
    }
    
    public function cacheTotalItems() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->where($this->where);
        $total = $this->core->xpdo->getCount('Brevis\Model\Item', $c);
        $this->core->cacheManager->set('items.total', $total, $this->core->cacheLifetime);
    }
}