<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;

class Stat extends Controller {

    public $name = 'Статистика';
    public $template = 'stat'; // шаблон страницы
    private $_sklads = array();

    /**
     * @return string
     */
    public function run() {

        if ($this->_sklads = $this->getSklads()) {
            $this->countItems();
            $this->getItemsUnpublished();
            $this->getItemsWithoutImages();
            $this->countItemImages();
        }
        // отдаем шаблон
        $data = array(
            'sklads' => $this->_sklads,
        );
        return $this->template($this->template, $data, $this);
    }

    /**
     * Выборка складов
     *
     * @return array
     */
    public function getSklads() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Sklad');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Sklad'));
//        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Sklad', 'Sklad', '', array('id', 'prefix')));
//        $c->sortby('mark_name', 'ASC');
//        $c->prepare();        die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать sklads:' . print_r($c->stmt->errorInfo(), true));
        }
        // prefix as key
        $res = array();
        foreach ($rows as $row) {
            $res[$row['prefix']] = array_merge($row, array(
                'items_total' => 0,
                'items_unpublished' => 0,
                'items_unpublished_list' => array(),
                'images_total' => 0,
                'images_prepared' => 0,
                'items_without_images' => 0,
                'items_without_images_list' => array(),
            ));
        }
//        var_dump($res);
        return $res;
    }

    /**
     * Кол-во и список неопубликованных позиций для складов
     * @return void
     */
    public function getItemsUnpublished() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select(array('prefix', 'code'));
        $c->where(array('published' => 0));
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->_sklads[$row['prefix']]['items_unpublished']++;
                $this->_sklads[$row['prefix']]['items_unpublished_list'][] = $row['code'];
            }
        } else {
            $this->core->log('Не могу выбрать Items:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Кол-во позиций для складов
     * @return void
     */
    public function countItems() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select(array('prefix', 'COUNT(*) AS `total`'));
        $c->groupby('prefix');
//        $c->prepare(); die($c->toSQL()); 
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->_sklads[$row['prefix']]['items_total'] = $row['total'];
            }
        } else {
            $this->core->log('Не могу выбрать Items:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Кол-во позиций для складов (вариант одним запросом опубликованные и нет)
     * @return void
     */
//    public function countItems() {
//        $rows = array();
//        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
//        $c->select(array('prefix', 'published', 'COUNT(*) AS `total`'));
//        $c->groupby('prefix, published');
////        $c->prepare(); die($c->toSQL()); 
//        if ($c->prepare() && $c->stmt->execute()) {
//            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
//            foreach ($rows as $row) {
//                $this->_sklads[$row['prefix']]['items_total'] += $row['total'];
//                if ($row['published'] == 0) {
//                    $this->_sklads[$row['prefix']]['items_unpublished'] = $row['total'];
//                }
//            }
//        } else {
//            $this->core->log('Не могу выбрать Items:' . print_r($c->stmt->errorInfo(), true));
//        }
//    }
    
    /**
     * Кол-во фото для складов
     * SELECT  `prefix` , COUNT( * ) , IF(  `filename` , 1, 0 ) AS  `prepared` 
     * FROM  `item_images` 
     * GROUP BY  `prefix` ,  `prepared` 
     * @return void
     */
    public function countItemImages() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\ItemImages');
        $c->select(array('prefix', 'COUNT(*) AS `total`', 'IF(`filename`, 1, 0 ) AS `prepared`'));
        $c->groupby('prefix, prepared');
//        $c->prepare();        die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->_sklads[$row['prefix']]['images_total'] += $row['total'];
                if ($row['prepared'] == 1) {
                    $this->_sklads[$row['prefix']]['images_prepared'] = $row['total'];
                }
            }
        } else {
            $this->core->log('Не могу выбрать ItemImages:' . print_r($c->stmt->errorInfo(), true));
        }
    }
    
    /**
     * Кол-во и список позиций без картинок для складов
     * 
     * @return void
     */
    public function getItemsWithoutImages() {
        /* 
         SELECT  item.code FROM  `item` 
         LEFT JOIN item_images ON item_images.item_id = item.id
         WHERE item_images.id IS NULL 
        */
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->select(array('Item.code', 'Item.prefix'));
        $c->leftJoin('Brevis\Model\ItemImages', 'ItemImages', 'ItemImages.item_id = Item.id');
        $c->where(array('ItemImages.id:IS' => null));
//        $c->prepare();        die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->_sklads[$row['prefix']]['items_without_images']++;
                $this->_sklads[$row['prefix']]['items_without_images_list'][] = $row['code'];
            }
        } else {
            $this->core->log('Не могу выбрать Items:' . print_r($c->stmt->errorInfo(), true));
        }
    }

}
