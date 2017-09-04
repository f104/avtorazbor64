<?php

/**
 * Поиск
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Search extends Controller {

    public $name = 'Поиск';
    
    /**
     * @var string Имя переменной с поисковым запросом в $_REQUEST
     */
    public $requestVar = 'sq';
    
    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->where = [
            'Item.published' => 1,
            'Item.moderate' => 1,
            'Item.error:IS' => null,
            'Item.sklad_id:IN' => $this->core->getSklads(),
        ];
        
    }
    
    public function run() {
        $redirect = null;
        if (isset($_REQUEST[$this->requestVar])) {
            $sq =  $this->core->cleanInput($_REQUEST[$this->requestVar]);
            if (!empty($sq)) {
                if ($item = $this->_searchItem($sq)) {
                    $this->success = true;
                    $redirect = $this->makeUrl('cars/item', [
                        'id' => $item->id,
                    ]);
                } elseif ($item = $this->_searchMark($sq)) {
                    $this->success = true;
                    $redirect = $this->makeUrl('cars', [
                        'mark' => $item->mark_key,
                    ]);                    
                } else {
                    $this->message = 'Увы, ничего не найдено.';
                }
            } else {
                $this->message = 'Введите поисковый запрос.';
            }
        } else {
            $this->message = 'Введите поисковый запрос.';
        }
        if ($this->isAjax) {
            // именно !success !!!
            $this->core->ajaxResponse(!$this->success, $this->message, ['redirect' => $redirect]);
        }
        if ($this->success) {
            $this->redirect($redirect);
        } else {
            return $this->template($this->template);
        }
    }
    
    /**
     * Поиск детали по нашему коду или коду производителя.
     * @param string $sq
     * @return Brevis\Model\Item or false
     */
    private function _searchItem($sq) {
        $sq = [
            $sq,
            str_replace(' ', '', $sq),
            str_replace('-', '', $sq),
            str_replace([' ', '-'], ['', ''], $sq),
        ];
        $sq = array_unique($sq);
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->where($this->where);
        
        foreach ($sq as &$q) {
            $q = "'$q'";
        }
        $sq = implode(',', $sq);
        $c->where("(code IN ($sq) OR vendor_code IN ($sq) OR REPLACE(REPLACE(`vendor_code`, '-', ''), ' ', '') IN ($sq))");
//        $c->prepare(); var_dump($c->toSQL()); die;
        return $item =  $this->core->xpdo->getObject('Brevis\Model\Item', $c);
    }
    
    /**
     * Поиск по названию марки
     * @param string $sq
     * @return Brevis\Model\Cars or false
     */
    private function _searchMark($sq) {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
        $c->innerJoin('Brevis\Model\Item', 'Item', ['Item.mark_key = Cars.mark_key']);
        $c->where($this->where);
        $c->where([
            'mark_name' => $sq,
        ]);
//        $c->prepare(); var_dump($c->toSQL()); die;
        if ($item =  $this->core->xpdo->getObject('Brevis\Model\Cars', $c)) {
            // Точное совпадение
            return $item;
        } else {
            // проверяем aliases
            if ($alias = $this->core->xpdo->getObject('Brevis\Model\MarkAlias', ['alias' => $sq])) {
                $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
                $c->innerJoin('Brevis\Model\Item', 'Item', ['Item.mark_key = Cars.mark_key']);
                $c->where($this->where);
                $c->where([
                    'mark_key' => $alias->mark_key,
                ]);
        //        $c->prepare(); var_dump($c->toSQL()); die;
                return $item =  $this->core->xpdo->getObject('Brevis\Model\Cars', $c);                
            }
        }
        return false;
    }
}