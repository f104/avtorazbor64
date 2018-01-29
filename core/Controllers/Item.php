<?php

/**
 * Конерктная деталь
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Controllers\Cars as Cars;

class Item extends Cars {

    public $template = 'item';
    public $item = null;
    
    function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : null;
        $this->where['id'] = $id;
        $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
        $c->leftJoin('Brevis\Model\BodyType', 'BodyType');
        $c->leftJoin('Brevis\Model\Condition', 'Condition');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Item', 'Item'));
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\BodyType', 'BodyType', 'bodytype_'));
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Condition', 'Condition', 'condition_'));
        $c->where($this->where);
//        $c->prepare(); var_dump($c->toSQL()); exit;
        if (empty($_REQUEST['id']) or !$this->item = $this->core->xpdo->getObject('Brevis\Model\Item', $c)) {
            $this->core->sendErrorPage();
        }
    }

    public function run() {
        // подключим поставщика, чтобы показать ему цену
        if ($this->core->isAuth and $this->supplier = $this->core->authUser->getOne('UserSupplier')) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\Sklad', ['supplier_id' => $this->supplier->id]);
            $c->select('id');
            if ($c->prepare() && $c->stmt->execute()) {
                $this->supplierSklads = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            }
        }
        
        // формируем крошки
        unset($this->breadcrumbs['cars']);
        $c = $this->core->xpdo->newQuery('Brevis\Model\Cars', [
            'mark_key' => $this->item->mark_key,
            'model_key' => $this->item->model_key,
            'LPAD(Cars.year_key,2,"0") = \''.$this->item->year_key.'\'', //FUCK!
        ]);
//        $c->prepare();
//        var_dump($c->toSQL());
        $car = $this->core->xpdo->getObject('Brevis\Model\Cars', $c);
        $urlParams = [
            'mark' => $car->mark_key,
        ];
        $this->breadcrumbs[$this->makeUrl('', $urlParams)] = $car->mark_name; 
        $urlParams['model'] = $car->model_key;
        $urlParams['year'] = sprintf("%'.02d", $car->year_key);
        $this->breadcrumbs[$this->makeUrl('', $urlParams)] = !empty($car->year_name) ? $car->year_name : $car->model_name; 
        $category = $this->item->getOne('Category');
        $urlParams['category'] = $category->key;
        $this->breadcrumbs[$this->makeUrl('', $urlParams)] = $category->name; 
        $element = $this->item->getOne('Element');
        $urlParams['element'] = $element->key;
        $this->breadcrumbs[$this->makeUrl('', $urlParams)] = $element->name; 
//        var_dump($this->breadcrumbs);
        
        $item = $this->item->toArray();
//        if ($this->showPrice) {
            // склад, нужен для рассчета
            $sklad = $this->item->getOne('Sklad');
            $country = $sklad->getOne('Country');
            $item['country_iso'] = $country->iso;
            $item['region_id'] = $sklad->region_id;
            $item['increase_category'] = $element->increase_category_id;
            $this->core->calculatePrice($item, $this->core->authUser);
//        }
        
        
        $this->name = $item['name'];
        $this->setSEO(['name' => $item['name']]);
//        $this->setPageTitle(['name' => $item['name']]);
//        $this->setPageDescription(['name' => $item['name']]);
        $data = [
            'item' => $item,
            'images' => $this->item->getImages(),
            'showPrice' => $this->showPrice,
            'canBuy' => $this->canBuy,
        ];
        
        return $this->template($this->template, $data, $this);
    }
          
}

class Requestphoto extends Item {
    
    public function run() {
        if (empty($_REQUEST['email'])) {
            $this->message = 'Укажите адрес электронной почты';
        } else {
            $emailUser = $this->core->xpdo->getObjectGraph('Brevis\Model\Supplier', ['User'], $this->item->supplier_id);
            $emailUser = $emailUser->User;
            $content = 'Запрос фото к детали '.$this->item->name.' ('.$this->item->code.").\r\n"
                . $this->core->siteUrl.'/item?id='.$this->item->id."\r\n"
                . "\r\n"
                . 'Дла ответа используйте e-mail: '.$_REQUEST['email'];

            $processor = $this->core->runProcessor('Mail\Send', [
                'toName' => $emailUser->name,
                'toMail' => $emailUser->email,
                'replyToName' => $this->core->isAuth ? $this->core->authUser->name : $_REQUEST['email'],
                'replyToMail' => $_REQUEST['email'],
                'subject' => 'Запрос фотографии',
                'body' => $this->template('_mail', [
                    'name' => $emailUser->name,
                    'content' => $content,
                    ], $this),
            ]);

            if (!$processor->isSuccess()) {
                $this->core->logger->error('Не удалось отправить письмо о новом заказе '.$order->id);
            } else {
                $this->success = true;
                $this->message = 'Запрос отправлен.';
            }
//    
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            return $this->message;
        }
    }
    
}