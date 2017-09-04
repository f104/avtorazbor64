<?php

/**
 * Проверка корректности кодов в базе
 * Запускается при обновлении каталога или выгрузки.
 * Ошибки пишет в поле error таблицы items
 * 
 * @param int $sklad_id (optional)
 */

namespace Brevis\Components\Checker;

use Brevis\Components\Component as Component;

class Checker extends Component {

    /** @var int $sklad_id (optional) */
    public $sklad_id = null;
    /** @var array $_report Report for logger */
    private $_report = [];
    /** @var array $errors_text Error messages */
    public $errors_text = [
        'car' => 'Ошибка соответствия марки/модели/модельного года',
        'element' => 'Ошибка соответствия категория/элемент',
    ];
    
    /**
     * Проверка Items по Cars
     * @return false|array id Item's with error      * 
     */
    private function _checkCars() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->leftJoin('Brevis\Model\Cars', 'Cars', 'Cars.mark_key = Item.mark_key AND
                Cars.model_key = Item.model_key AND 
                LPAD(Cars.year_key, 2, 0) = Item.year_key'
        );
        $c->select('Item.id');
        $where = ['Cars.id:IS' => null];
        if (!empty($this->sklad_id)) {
            $where['Item.sklad_id'] = $this->sklad_id;
        }
        $c->where($where);
//            $c->prepare(); die ($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            return $rows = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $this->logger->error('Не могу выбрать item:' . print_r($c->stmt->errorInfo(), true));
            return false;
        }
    }

    /**
     * Проверка Items по Element/Category
     * @return false|array id Item's with error      * 
     */
    private function _checkElement() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->leftJoin('Brevis\Model\Element', 'Element', 'Element.key = Item.element_key');
        $c->leftJoin('Brevis\Model\Category', 'Category', 'Category.key = Item.category_key AND Category.key = Element.category_key');
        $c->select('Item.id');
        $where = [
            'Element.key:IS' => null,
            'OR:Category.key:IS' => null
        ];
        if (!empty($this->sklad_id)) {
            $where['Item.sklad_id'] = $this->sklad_id;
        }
        $c->where($where);
//            $c->prepare(); die ($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            return $rows = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $this->logger->error('Не могу выбрать item:' . print_r($c->stmt->errorInfo(), true));
            return false;
        }
    }

    /**
     * Mark Items as have error
     * @param array $items id
     * @return void
     */
    private function _markErrorItems($items, $error) {
        $set = ['error' => $error];
        $criteria = ['id:IN' => $items];
        $this->core->xpdo->updateCollection('Brevis\Model\Item', $set, $criteria);
    }

    /**
     * Unmark Items as have error
     * @return void
     */
    private function _unmarkErrorItems() {
        $set = ['error' => null];
        $criteria = null;
        if (!empty($this->sklad_id)) {
            $criteria = ['sklad_id' => $this->sklad_id];
        }
        $this->core->xpdo->updateCollection('Brevis\Model\Item', $set, $criteria);
    }

    /**
     * {@inheritDoc}
     */
    public function run() {
        if (!empty($this->sklad_id)) {
            $this->_report[] = 'Склад: ' . $this->sklad_id;
        }
        $this->_unmarkErrorItems();
        $haveErrors = false;
        if ($errorCars = $this->_checkCars()) {
            $this->_report[] = $this->errors_text['car'] . ':' . count($errorCars);
            $this->_markErrorItems($errorCars, $this->errors_text['car']);
            $haveErrors = true;
        }
        if ($errorElement = $this->_checkElement()) {
            $this->_report[] = $this->errors_text['element'] . ':' . count($errorElement);
            $this->_markErrorItems($errorElement, $this->errors_text['element']);
            $haveErrors = true;
        }
        if (!$haveErrors) {
            $this->_report[] = 'Ошибок не найдено.';
        }
        $this->logger->info(implode("\r\n", $this->_report));
    }

}
