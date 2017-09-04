<?php

/**
 * Управление товарами
 */
    
namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;
use Brevis\Components\Tgenerator\Tgenerator as Tgenerator;
use Brevis\Components\EventLogger\EventLogger as EventLogger;
use Picqer\Barcode\BarcodeGeneratorPNG as BarcodeGenerator;

class Items extends Controller {

    public $name = 'Товары';
    public $permissions = ['items_view'];
    public $langTopic = 'item';
    public $additionalJs = [
        '/assets/js/items.js',
        '/assets/js/bootstrap-fileinput/js/plugins/canvas-to-blob.min.js',
        '/assets/js/bootstrap-fileinput/js/fileinput.min.js',
        '/assets/js/bootstrap-fileinput/js/locales/ru.js',
        '/assets/js/bootstrap-fileinput/themes/fa/theme.js',
        '/assets/js/jquery-sortable-min.js',
    ];
    public $additionalCSS = [
        '/assets/js/bootstrap-fileinput/css/fileinput.min.css',
    ];
    
    public $classname = 'Brevis\Model\Item';
    
    
    /**
     * Пользователь - поставщик
     * @var Brevis\Model\Supplier
     */
    public $supplier = null;
    public $isManager = false; // работает менеджер/админ
    
    /** @var string Почта для получения уведомлений о запросах на добавдение информации в каталог */
    public $emailRequestTo = 'onlinezakazsar@mail.ru';
    
    /**
     * @var array [id]=> cols
     */
    public $sklads = [];
    public $skladsSelect = []; // склады для селектов
    
    public $limit = PROJECT_DEFAULT_QUERY_LIMIT;
    public $page = 1;
    private $_offset = 0;
    private $_total = 0;
    
    public $allowedFilters = ['sklad_id', 'published', 'moderate', 'images', 'code', 'error', 'body_type', 'condition'];
    public $filters = [];
    public $allowedSort = ['name', 'price', 'code', 'sklad_id', 'published', 'updatedon'];
    public $defaultSort = 'updatedon';
    public $sortdir = 'DESC';


    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $this->eventLogger = new EventLogger($this->core, $this->lang, $this->lang['items']);
        $this->eventLogger->langPrefix = 'item.';
        $this->supplier = $this->core->authUser->getOne('UserSupplier');
        if (empty($this->supplier)) {
            $this->isManager = $this->core->authUser->checkPermissions('items_manage');
        }
        $c = $this->core->xpdo->newQuery('\Brevis\Model\Sklad');
        // нужны все данные, не только для селекта
//        $c->select(['id', "CONCAT_WS('', name, ' (', prefix, ')')"]);
        $c->select($this->core->xpdo->getSelectColumns('\Brevis\Model\Sklad', 'Sklad'));
        $c->sortby('name');
        if ($this->supplier) {
            $c->where(['supplier_id' => $this->supplier->id]);
        }
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->sklads[$row['id']] = $row;
                $this->skladsSelect[$row['id']] = $row['name'].' ('.$row['prefix'].')';
            }
        } else {
            $this->core->logger->error('Не могу выбрать Sklad:' . print_r($c->stmt->errorInfo(), true));
        }
        $this->_readFilters($_GET);
        if ($this->isManager) {
            $this->exportColumns = ['id', 'name', 'price', 'code', 'vendor_code', 'condition', 'sklad_prefix', 
                'published', 'moderate', 'moderate_message', 'reserved', 'source', 'error', 'updatedon'];
        } else {
            $this->exportColumns = ['id', 'name', 'price', 'code', 'vendor_code', 'condition', 'sklad_prefix', 
                'published', 'moderate', 'moderate_message', 'reserved', 'error', 'updatedon'];
        }
    }
    
    public function getRows($raw = false) {
        $items = [];
        if ($this->supplier) {
            $this->where['sklad_id:IN'] = array_keys($this->sklads);
        }
        if (!empty($this->filters['sklad_id'])) {
            $this->where['sklad_id'] = $this->filters['sklad_id'];
        }
        if (isset($this->filters['published'])) {
            $this->where['published'] = $this->filters['published'];
        }
        if (isset($this->filters['moderate'])) {
            $this->where['moderate'] = $this->filters['moderate'];
        }
        if (!empty($this->filters['code'])) {
            $this->where['code:LIKE'] = '%'.$this->filters['code'].'%';
        }
        if (isset($this->filters['body_type'])) {
            $this->where['body_type'] = $this->filters['body_type'];
        }
        if (isset($this->filters['condition'])) {
            $this->where['condition'] = $this->filters['condition'];
        }
        if (isset($this->filters['error'])) {
            switch ($this->filters['error']) {
                case 1: $this->where['error:IS NOT'] = null; break;
                case 0: $this->where['error:IS'] = null; break;
            }
        }
        $c = $this->core->xpdo->newQuery($this->classname);
        // images
        if (isset($this->filters['images'])) {
            $c->leftJoin('Brevis\Model\ItemImages', 'ItemImages', ('ItemImages.item_id = Item.id'));
            if ($this->filters['images'] == 0) {
                $this->where['ItemImages.id:IS'] = null;
            } else {
                $this->where['ItemImages.id:IS NOT'] = null;
                $c->distinct();
            }
        }
        $c->where($this->where);
        $this->_total = $this->core->xpdo->getCount($this->classname, $c);
        if (!$raw) {
            if ($this->page < 1 or $this->page > ceil($this->_total / $this->limit)) {
                $this->page = 1;
                $this->filters['page'] = 1;
            }
            $this->_offset = $this->limit * ($this->page - 1);
            $c->limit($this->limit, $this->_offset);
        }
        $c->select($this->core->xpdo->getSelectColumns($this->classname, 'Item'));
        $c->leftJoin('Brevis\Model\Sklad', 'Sklad', ('Sklad.id = Item.sklad_id'));
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Sklad', 'Sklad', 'sklad_', ['prefix']));
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); echo $c->toSQL();
        if ($c->prepare() && $c->stmt->execute()) {
            $items = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать ' . $this->classname . ':' . print_r($c->stmt->errorInfo(), true));
        }
        return $items;
    }
    
    /**
     * Следующий счетчик для товара.
     * @param int $supplierId 
     * @param array $fields form fields
     * @param int $itemId Item ID for update
     * @return int or boolean on error
     */
    protected function _getNextCounter($supplierId, $fields) {
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->select('MAX(`counter`)');
        $where = [
            'supplier_id' => $supplierId,
            'mark_key' => $fields['mark_key'],
            'model_key' => substr($fields['model_key'], 0, 3),
            'year_key' => substr($fields['model_key'], -2),
            'category_key' => $fields['category_key'],
            'element_key' => $fields['element_key'],
        ];
        $c->where($where);
        if ($c->prepare() and $c->stmt->execute()) {
            $res = $c->stmt->fetch(\PDO::FETCH_COLUMN);
            $res++;
            return $res;
        }
        return false;
    }
    
    /**
     * список товаров (пользователя или всех)
     */
    public function run() {
        if (empty($this->sklads)) {
            $this->message = $this->lang['item.one_sklad_needed'];
            return $this->template($this->template);
        }

        // фильтры
        $filters = new FormFilters([], $this);
        $filters->select('sklad_id')->addLabel($this->lang['item.sklad_id'])->setSelectOptions($this->skladsSelect);
        $f = $filters->select('published')->addLabel($this->lang['item.published'])->setSelectOptions(['Нет', 'Да']);
        $filters->select('moderate')->addLabel($this->lang['item.moderate'])->setSelectOptions([
            0 => 'Ожидает',
            -1 => 'Отказ',
            1 => 'Одобрен']);
        $filters->select('images')->addLabel($this->lang['item.images'])->setSelectOptions([
            0 => 'Нет',
            1 => 'Есть']);
        $filters->select('error')->addLabel($this->lang['item.error'])->setSelectOptions(['Нет', 'Да']);
        $filters->input('code', ['placeholder' => $this->lang['filters_like_ph']])->addLabel($this->lang['item.code']);
        $filters->select('body_type')->addLabel($this->lang['item.body_type'])->setSelectOptions($this->getBodytypes());
        $filters->select('condition')->addLabel($this->lang['item.condition'])->setSelectOptions($this->getConditions());
        
        $items = $this->getRows();
        
        $cols = [
            'name' => 
                ['title' => $this->lang['item.name'], 'tpl' => '@INLINE <a href="'.$this->uri.'/view?id={$row.id}&'.  http_build_query($this->filters).'">{$row.name}</a>'],
            'price' => 
                ['title' => $this->lang['item.price']],
            'code' => [
                    'title' => $this->lang['item.code'],
                    'class' => 'text-nowrap',
                    'tpl' => '@INLINE {$row.code}{if $row.error} <i class="fa fa-exclamation-triangle text-danger message-in-title" title="{$row.error|e}"></i>{/if}{if $row.remote_id?}<br>({$row.remote_id}){/if}',
                ],
            'updatedon' => 
                ['title' => $this->lang['item.updatedon'], 'class' => 'text-nowrap', 'tpl' => '@INLINE {if $row.updatedon?}{$row.updatedon|date_format:"%d-%m-%Y %H:%M"}{/if}'],
            'sklad_id' => 
                ['title' => $this->lang['item.sklad_id'], 'tpl' => '@INLINE {$row.sklad_prefix}'],
            'published' => 
                ['title' => $this->lang['item.published'], 'tpl' => '@INLINE <input class="js-item-published-checkbox" data-id="{$row.id}" type="checkbox" {$row.published ? "checked" : ""}>'],
            'sticker' => 
                ['title' => $this->lang['item.sticker'], 'tpl' => '@INLINE <a class="link-clear" href="'.$this->uri.'/sticker?id={$row.id}" target="_blank"><i class="fa fa-print"></i></a>', 'sortable' => false],
        ];
        if ($this->isManager) {
            $cols['moderate'] = ['title' => '', 'class' => 'text-nowrap', 'sortable' => false, 'tpl' => '@INLINE '
                . '<button class="btn btn-xs btn-primary js-item-moderate" data-id="{$row.id}" {if $row.moderate == 1}disabled{/if} title="Одобрить"><i class="fa fa-check"></i></button> '
                . '<button class="btn btn-xs btn-warning js-item-unmoderate" data-id="{$row.id}" {if $row.moderate == -1}disabled{/if} title="Отклонить"><i class="fa fa-close"></i></button> '
                . '<button class="btn btn-xs btn-danger js-item-remove" data-id="{$row.id}" title="Удалить"><i class="fa fa-trash-o"></i></button>'];
        } else {
            $cols['moderate'] = ['title' =>'', 'class' => 'text-nowrap', 'sortable' => false, 'tpl' => '@INLINE '
                . '{if $row.moderate == 1}<span class="text-success">Одобрен</span>{/if}'
                . '{if $row.moderate == 0}Ожидает{/if}'
                . '{if $row.moderate == -1}'
                . '<span class="text-danger">Отказ</span>'
                . '{if $row.moderate_message} <i class="message-in-title fa fa-info-circle" title="{$row.moderate_message|e}"></i>{/if}'
                . '{/if}'];
            $cols['remove'] = ['title' =>'', 'sortable' => false, 'tpl' => '@INLINE <button class="btn btn-xs btn-danger js-item-remove" data-id="{$row.id}" title="Удалить"><i class="fa fa-trash-o"></i></button>'];
        }
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $items,
            'total' => $this->_total,
            'offset' => $this->_offset,
            'pagination' => $this->getPagination($this->_total, $this->page, $this->limit),
            'addPermission' => 'items_add',
            'addUrl' => $this->makeUrl($this->uri.'/add', $this->filters),
        ], $this);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $content]);
        }
        
        // форма причины отказа
        $form = new Form([
            'id' => 'moderate_form',
            'class' => 'white-popup-block mfp-hide',
            'action' => 'items/moderate'
        ], $this);
        $form->hidden('id');
        $form->hidden('moderate')->setValue(-1);
        $form->textarea('moderate_message')->addLabel($this->lang['item.moderate_message']);
        $form->button('Отказать', ['type' => 'submit', 'class' => 'btn btn-danger']);
        $form->link('Отмена', ['href' => '#']);
        $content = $content.$form->draw();
        
        // форма удаления
        $form = new Form([
            'id' => 'remove_form',
            'class' => 'white-popup-block mfp-hide',
            'action' => 'items/remove'
        ], $this);
        $form->legend($this->lang['item.confirm_remove']);
        $form->hidden('id');
        $form->button('Удалить', ['type' => 'submit', 'class' => 'btn btn-danger']);
        $form->link('Отмена', ['href' => '#']);
        
        $data = [
            'content' => $content.$form->draw(),
        ];
        return $this->template($this->template, $data, $this);        
    }
    
    protected function form(\Brevis\Model\Item $item) {
        $form = new Form([
            'id' => 'item_form',
            'class' => 'j1s-ajaxform',
        ], $this);
        $marks = $this->getMarks();
        $categories = $this->getCategories();
        $form->select('mark_key', ['required'])->addLabel($this->lang['item.mark'])
            ->withEmptyOption()
            ->setSelectOptions($marks)
            ->setValue($item->mark_key);
        $form->select('model_key', ['required'])->addLabel($this->lang['item.model'])
            ->withEmptyOption()
            ->setSelectOptions($this->getModels($item->mark_key ?: key($marks)))
            ->setValue($item->model_key.$item->year_key)
            ->addHelp('Нужной модели нет в списке? <a href="#request_car_form" class="dotted js-inlinepopup">Предложите свою.</a>');
        $form->select('category_key', ['required'])->addLabel($this->lang['item.category'])
            ->withEmptyOption()
            ->setSelectOptions($categories)
            ->setValue($item->category_key);
        $form->select('element_key', ['required'])->addLabel($this->lang['item.element'])
            ->withEmptyOption()
            ->setSelectOptions($this->getElements($item->category_key ?: key($categories)))
            ->setValue($item->element_key)
            ->addHelp('Нужного элемента нет в списке? <a href="#request_category_form" class="dotted js-inlinepopup">Предложите свой.</a>');
        $form->input('vendor_code', ['maxlength' => 20])->addLabel($this->lang['item.vendor_code'])->setValue($item->vendor_code);
        $form->input('name', ['required', 'readonly', 'maxlength' => 500])->addLabel($this->lang['item.name'])->setValue($item->name);
        $form->select('condition')
            ->addLabel($this->lang['item.condition'], 'help?article=items')
            ->setSelectOptions($this->getConditions())->setValue($item->condition);
        $form->input('condition_comment', ['maxlength' => 255])->addLabel($this->lang['item.condition_comment'])->setValue($item->condition_comment);
        $form->select('body_type')
            ->withEmptyOption()
            ->addLabel($this->lang['item.body_type'])
            ->addHelp($this->lang['item.body_type_desc'])
            ->setSelectOptions($this->getBodytypes())->setValue($item->body_type);
//        $form->input('kol', ['type' => 'number', 'min' => 1, 'pattern' => '\d+', 'required'])->addLabel($this->lang['item.kol'])->setValue($item->kol);
        $form->input('price', ['type' => 'number', 'min' => 1, 'required'])
            ->addLabel($this->lang['item.price'])
            ->setValue($item->price)
            ->addHelp('<span style="display: none">Рекомендуемая цена: <span id="average_price" class="dotted"></span> руб.</span>');;
        $form->textarea('comment', ['rows' => 5])->addLabel($this->lang['item.comment'])->setValue($item->comment)->addHelp($this->lang['item.comment_desc']);
        $form->select('sklad_id')->addLabel($this->lang['item.sklad_id'])->setSelectOptions($this->skladsSelect)->setValue($item->sklad_id);
        if ($item->id) {
            // не показываем для нового товара
            $form->checkbox('published')->addLabel($this->lang['item.published'])->setValue($item->published);
        }
        if ($this->isManager) {
//            $_lang['moderate'] = 'Одобрен';
//$_lang['moderate.wait'] = 'Ожидает';
//$_lang['moderate.yes'] = 'Да';
//$_lang['moderate.no'] = 'Нет';
//$_lang['moderate_message'] = 'Причина отказа или иное сообщение пользователю';
            $form->select('moderate')->addLabel($this->lang['item.moderate'])
                ->setSelectOptions([
                    0 => $this->lang['item.moderate.wait'],
                    1 => $this->lang['item.moderate.yes'],
                    -1 => $this->lang['item.moderate.no'],
                    ])
                ->setValue($item->moderate);
            $form->input('moderate_message', ['maxlength' => 255])->addLabel($this->lang['item.moderate_message'])->setValue($item->moderate_message);
//            $form->checkbox('moderate')->addLabel($this->lang['item.moderate'])->setValue($item->moderate);
        }
        $form->button('Сохранить', ['type' => 'submit', 'name' => 'item_submit']);
        $form->link('Отмена', ['href' => $this->makeUrl('parent', $this->filters)]);
        return $form;
    }
    
    /**
     * Форма запроса на добавление марки/модели
     * @return Form
     */
    protected function _formRequestCar() {
        $form = new Form([
            'id' => 'request_car_form',
            'class' => 'white-popup-block mfp-hide js-ajaxform js-catalog-request',
            'action' => 'items/requestcar',
        ], $this);
        $marks = $this->getMarks();
        $form->select('mark_select')->addLabel($this->lang['item.mark'])
            ->setSelectOptions($marks)
            ->addHelp('Выберите из списка</span> или <span class="dotted" data-show="mark_input">введите вручную</span>');
        $form->input('mark_input', ['style' => 'display:none']);
        $form->input('model', ['required'])->addLabel($this->lang['item.model']);
        $form->button('Отправить', ['type' => 'submit']);
        $form->button('Отмена', ['type' => 'reset', 'class' => 'btn btn-default']);
        return $form;
    }
    
    /**
     * Форма запроса на добавление категории/элемента
     * @return Form
     */
    protected function _formRequestCategory() {
        $form = new Form([
            'id' => 'request_category_form',
            'class' => 'white-popup-block mfp-hide js-ajaxform js-catalog-request',
            'action' => 'items/requestcategory',
        ], $this);
        $categories = $this->getCategories();
        $form->select('category_select')->addLabel($this->lang['item.category'])
            ->setSelectOptions($categories)
            ->addHelp('Выберите из списка</span> или <span class="dotted" data-show="category_input">введите вручную</span>');
        $form->input('category_input', ['style' => 'display:none']);
        $form->input('element', ['required'])->addLabel($this->lang['item.element']);
        $form->button('Отправить', ['type' => 'submit']);
        $form->button('Отмена', ['type' => 'reset', 'class' => 'btn btn-default']);
        return $form;
    }
    
    protected function getMarks() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Cars', 'Cars', '', array('mark_key','mark_name')));
        $c->distinct();
        $c->sortby('mark_name', 'ASC');
//            $c->innerJoin('Brevis\Model\Item', 'Item', 'Item.mark_key=Cars.mark_key');
//            $c->where(array('mark_key:!='=>'','mark_name:!='=>''));
//            if (!$this->showUnpublished) {
//                $where['Item.published'] = 1;            
//            }
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->logger->error('Не могу выбрать marks:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;        
    }
    
    public function getModels($mark_key) {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Cars');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Cars', 'Cars'));
        $c->distinct();
        $c->sortby('model_name', 'ASC');
        $where = [
            'mark_key'=> $mark_key,
            'year_key:!=' => '',
            'year_name:!=' => '',
        ];
//        $where[] = "Item.year_key = LPAD(Cars.year_key,2,'0')"; //FUCK!
        $c->where($where);
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
        } else {
            $this->core->log('Не могу выбрать models:' . print_r($c->stmt->errorInfo(), true));
        }
        $models = array();
        foreach ($rows as $row) {
            $row['year_key'] = sprintf("%'.02d", $row['year_key']);
//            $row['uri'] = $this->uri.'?'.http_build_query(array(
//                'mark' => $row['mark_key'],
//                'model' => $row['model_key'],
//                'year' => $row['year_key'],
//                ));
//            $row['name'] = $row['year_name'] ?: $row['model_name'];
            $models[$row['model_key'].$row['year_key']] = $row['year_name'];
        }
        return $models;
    }
    
    /**
     * Выборка категорий
     * 
     * @param string $mark_key
     * @param string $model_key
     * @param string $year_key
     * 
     * @return array||redirect
     */
    public function getCategories() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Category');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Category', 'Category', '', ['key', 'name']));
//        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Item', 'Item', '', array('mark_key','model_key','year_key')));
        $c->distinct();
        $c->sortby('name', 'ASC');
//        $c->where($where);
//        $c->prepare(); var_dump($c->toSQL()); exit;
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать category:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    /**
     * Выборка элементов
     *
     * @params string $mark_key, $model_key, $year_key, $category_key
     * @return array||redirect
     */
    public function getElements($category_key) {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Element');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Element', 'Element', '', ['key', 'name']));
        $c->where(['category_key'=> $category_key]);
        $c->distinct();
        $c->sortby('name', 'ASC');
//        $c->where($where);
//        $c->prepare(); var_dump($c->toSQL()); exit;
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать element:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    /**
     * Выборка типов кузова
     *
     * @return array
     */
    public function getBodytypes() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\BodyType');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\BodyType', 'BodyType'));
        $c->sortby('name', 'ASC');
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
        } else {
            $this->core->log('Не могу выбрать element:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    /**
     * Выборка типов износа
     *
     * @return array
     */
    public function getConditions() {
        $rows = array();
        $c = $this->core->xpdo->newQuery('Brevis\Model\Condition');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\Condition', 'Condition', '', ['id','name']));
        $c->sortby('id', 'ASC');
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            foreach ($rows as $k => $v) {
                $rows[$k] = str_replace(['{$id}', '{$name}'], [$k, $v], $this->lang['item.condition_option']);
            }
        } else {
            $this->core->log('Не могу выбрать element:' . print_r($c->stmt->errorInfo(), true));
        }
        return $rows;
    }
    
    /**
     * Обновляет время "изменения" склада
     * @param int $id
     */
    public function updateSkladTime($id) {
        if ($sklad = $this->core->xpdo->getObject('Brevis\Model\Sklad', $id)) {
            $sklad->updateTime();
        }
    }
    
    /**
     * Отправка письма администратору о добавлении новых данных в каталог
     * @param array $fields
     * @return bool
     */
    protected function _sendRequestMail($fields) {
        $processor = $this->core->runProcessor('Mail\Send', [
            'toMail' => $this->emailRequestTo,
            'subject' => 'Запрос на добавление данных в каталог',
            'body' => $this->template('mail/items/request', [
                'user' => $this->core->authUser->toArray(),
                'fields' => $fields,
                ], $this),
        ]);
        if (!$processor->isSuccess()) {
            $this->core->logger->error(static::class . 'Не удалось отправить письмо ' . $processor->getError());
            return false;
        }
        return true;
    }
    
}

class Get extends Items {

}

class Cars extends Get {
    public function run() {
        $template = '<option value="{$k}">{$v}</option>';
        $res = [];
        if (isset($_REQUEST['mark'])) {
            // модели
            $res = $this->getModels($_REQUEST['mark']);
        }
        if (isset($_REQUEST['category'])) {
            // элементы
            $res = $this->getElements($_REQUEST['category']);
        }
        if ($this->isAjax) {
//            $res = $this->template($template, $res);
            foreach ($res as $k=>$v) {
                $res[$k] = str_replace(['{$k}', '{$v}'], [$k, htmlspecialchars($v)], $template);
            }
            $this->core->ajaxResponse(true, '', ['results' => implode('', $res)]);
        } else {
            return print_r($res, true);
        }
    }
}

class Price extends Get {
    // SELECT ROUND(AVG(`price`))
    public function run() {
        $result = 0;
        if (!empty($_REQUEST['category']) and !empty($_REQUEST['element'])) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\Item');
            $c->select('ROUND(AVG(`price`)) AS av_price');
            $where = ['category_key' => $_REQUEST['category'], 'element_key' => $_REQUEST['element']];
            // средняя для всех марок
            $c->where($where);
            if ($c->prepare() and $c->stmt->execute()) {
                $row = $c->stmt->fetch(\PDO::FETCH_COLUMN);
                if (!empty($row)) {
                    $this->success = true;
                    $result = $row;
                }
                // средняя для марки/модели
                if (!empty($_REQUEST['mark']) and !empty($_REQUEST['model'])) {
                    $c->where([
                        'mark_key' => $_REQUEST['mark'],
                        'model_key' => substr($_REQUEST['model'], 0, 3),
                        'year_key' => substr($_REQUEST['model'], -2),
                    ]);
                    if ($c->prepare() and $c->stmt->execute()) {
                        $row = $c->stmt->fetch(\PDO::FETCH_COLUMN);
                        if (!empty($row)) {
                            $result = $row;
                        }
                    }
                }
            }
        }
        $this->core->ajaxResponse($this->success, '', ['result' => $result]);
    }
}

/**
* Новый товар.
*/
class Add extends Items {
    
    public $name = 'Новый товар';
    public $permissions = ['items_add'];
    public $template = 'items.view';


    public function run() {
        
        $item = $this->core->xpdo->newObject('Brevis\Model\Item');
        // если пришли с фильтром по складу, то логично поставить этот склад
        if (!empty($this->filters['sklad_id'])) {
            $item->sklad_id = $this->filters['sklad_id'];
        }
        $form = $this->form($item);
        
        if ($form->process()) {
            $fields = $form->getValues();
            // проверим склад
            if (!array_key_exists($fields['sklad_id'], $this->sklads)) {
                $form->addError('sklad_id', 'Sklad_id incorrect');
            }
            if (!$form->hasError()) {
                $item->fromArray($fields);
                // поставщик
                $supplierId = $this->sklads[$fields['sklad_id']]['supplier_id'];
                $item->set('supplier_id', $supplierId);
                $item->set('prefix', $this->sklads[$fields['sklad_id']]['prefix']); /** @TODO delete */
                // формируем счетчик
                $counter = $this->_getNextCounter($supplierId, $fields);
                if ($counter === false or $counter == 10000) {
                    $this->message = 'Не удалось сформировать счетчик, сообщите администратору.';
                } else {
                    $item->set('counter', $counter);
                    $item->save();
                    $this->eventLogger->add($item->id);
                    $this->updateSkladTime($fields['sklad_id']);
                    $this->redirect($this->makeUrl('items/view', array_merge(['id' => $item->id], $this->filters)));
                }
            } else {
                $this->message = 'Исправьте ошибки в форме.';
            }
        }
        
        $formRequestCar = $this->_formRequestCar();
        $formRequestCategory = $this->_formRequestCategory();
        
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
        }
        
        $data = [
            'formEdit' => $form->draw(),
            'formRequestCar' => $formRequestCar->draw(),
            'formRequestCategory' => $formRequestCategory->draw(),
        ];
        return $this->template($this->template, $data, $this);
    }
    
}

/**
* Просмотр, редактирование товара
*/
class View extends Items {
    
    public $name = 'Просмотр/редактирование товара';
    public $permissions = ['items_add'];
    public $template = 'items.view';
    
    public function run() {
        
        if (empty($_REQUEST['id']) or !$item = $this->core->xpdo->getObject('Brevis\Model\Item', ['id:=' => $_REQUEST['id'], 'sklad_id:IN' => array_keys($this->sklads)])) {
            $this->redirect('parent');
        }
        $form = $this->form($item);
        
        if (isset($_REQUEST['item_submit']) and $form->process()) {
            $fields = $form->getValues();
            // проверим склад
            if (!array_key_exists($fields['sklad_id'], $this->sklads)) {
                $form->addError('sklad_id', 'Sklad_id incorrect');
            }
            if (!$form->hasError()) {
                if ($form->hasChanged()) {
                    if (!$item->getMany('Images') and !empty($fields['published'])) {
                        $form->addError('published', $this->lang['item.error.published_no_photo']);
                    } else {
                        $loggerOld = $item->toArray();
                        // поставщик
                        $supplierId = $this->sklads[$fields['sklad_id']]['supplier_id'];
                        // формируем счетчик
                        if (
                            $fields['mark_key'] != $item->mark_key 
                            or substr($fields['model_key'], 0, 3) != $item->model_key
                            or substr($fields['model_key'], -2) != $item->year_key
                            or $fields['category_key'] != $item->category_key
                            or $fields['element_key'] != $item->element_key
                            or $supplierId != $item->supplier_id
                        ) {
                            // Нужен новый счетчик
                            $counter = $this->_getNextCounter($supplierId, $fields);
                        } else {
                            $counter = $item->counter;                            
                        }
                        if ($counter === false or $counter == 10000) {
                            $this->message = 'Не удалось сформировать счетчик, сообщите администратору.';
                        } else {
                            $item->fromArray($fields);
                            $item->set('supplier_id', $supplierId);
                            $item->set('prefix', $this->sklads[$fields['sklad_id']]['prefix']); /** @TODO delete */
                            $item->set('counter', $counter);
                            $item->set('code', ''); // сгененируется автоматически в классе
                            if (!$this->isManager) {
                                $item->set('moderate', 0);
                            }
                            $item->save();
                            $loggerNew = $item->toArray();
                            unset($loggerNew['counter'], $loggerNew['code']);
                            $this->eventLogger->update($item->id, $loggerOld, $loggerNew);
                            $this->updateSkladTime($fields['sklad_id']);
                        }
                    } 
                }
                if (!$form->hasError()) {
                    $this->redirect($this->makeUrl('parent', $this->filters));
                } else {
                    $this->message = 'Исправьте ошибки в форме.';
                }
            } else {
                $this->message = 'Исправьте ошибки в форме.';
            }
        }
        $formRequestCar = $this->_formRequestCar();
        $formRequestCategory = $this->_formRequestCategory();
        
        $formImages = new Form([
            'enctype' => 'multipart/form-data',
            'id' => 'form_images_add',
        ], $this);
        $formImages->hidden('item_id')->setValue($item->id);
        $formImages->input('files[]', [
            'type' => 'file',
            'id' => 'file_upload',
            'multiple',
        ])->addLabel($this->lang['item.upload_images']);
        
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $form->getErrors()]);
        }
        
        $imagesCriteria = $this->core->xpdo->newQuery('Brevis\Model\ItemImages');
        $imagesCriteria->where(['binary:IS' => null]);
        $imagesCriteria->sortby('`order`');
        $data = [
            'formEdit' => $form->draw(),
            'formRequestCar' => $formRequestCar->draw(),
            'formRequestCategory' => $formRequestCategory->draw(),
            'formImages' => $formImages->draw(),
            'images' => $item->getMany('Images', $imagesCriteria),
            'imagesBinary' => $this->core->xpdo->getCount('Brevis\Model\ItemImages', ['item_id:=' => $item->id, 'binary:IS NOT' => null]),
            'item_id' => $item->id,
        ];
        if (!$this->isManager) {
            $data['statusMessage'] = $this->lang['item.statusMessage'];
        }
        return $this->template($this->template, $data, $this);
    }
}

/**
 * Публикация / снятие с публикации
 */
class Publish extends Items {
    public $permissions = ['items_add'];
    public function run() {
        if (!empty($_REQUEST['id']) and isset($_REQUEST['publish'])) { // publish may be 0
            $published = empty($_REQUEST['publish']) ? 0 : 1;
            if ($item = $this->core->xpdo->getObject('Brevis\Model\Item', $_REQUEST['id'])) {
                if ($published == 1 and !$item->getMany('Images')) {
                    $this->message = $this->lang['item.error.published_no_photo'];
                } else {
                    if (array_key_exists($item->sklad_id, $this->sklads)) {
                        $loggerOld = $item->toArray();
                        $item->set('published', $published);
                        $item->save();
                        $this->success = true;
                        $this->message = 'Сохранено';
                        $this->eventLogger->update($item->id, $loggerOld, $item->toArray());
                    }
                }
            }
        } else {
            $this->message = 'Что-то пошло не так...';
            $this->core->sendErrorPage();
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            die($this->message);
        }
    }
}

/**
 * Модерирование
 */
class Moderate extends Items {
    public $permissions = ['items_manage'];
    public function run() {
        if (!empty($_REQUEST['id']) and isset($_REQUEST['moderate']) and in_array($_REQUEST['moderate'], [-1, 0, 1])) {
            if ($item = $this->core->xpdo->getObject('Brevis\Model\Item', $_REQUEST['id'])) {
                $loggerOld = $item->toArray();
                $item->set('moderate_message', !empty($_REQUEST['moderate_message']) ? $this->core->cleanInput($_REQUEST['moderate_message']) : null);
                $item->set('moderate', $_REQUEST['moderate']);
                $item->save();
                $this->eventLogger->update($item->id, $loggerOld, $item->toArray());
                $this->success = true;
                $this->message = 'Сохранено';
            }
        } else {
            $this->message = 'Что-то пошло не так...';
            $this->core->sendErrorPage();
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            die($this->message);
        }
    }
}

/**
 * Удаление
 */
class Remove extends Items {
    public function run() {
        if (!empty($_REQUEST['id'])) {
            $where = ['id' => $_REQUEST['id']];
            if ($this->supplier) {
                $where['supplier_id'] = $this->supplier->id;
            }
//            var_dump($where);die;
            if ($item = $this->core->xpdo->getObject('Brevis\Model\Item', $where)) {
                if ($item->remove()) {
                    $this->eventLogger->remove($item->id);
                    $this->success = true;
                    $this->message = 'Удалено';
                }
            } else {
                $this->message = 'Что-то пошло не так...';
                $this->core->sendErrorPage();
            }
        } else {
            $this->message = 'Что-то пошло не так...';
            $this->core->sendErrorPage();
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            die($this->message);
        }
    }
}

class Images extends Items {
    public $permissions = ['items_add'];
    
    /**
     * Проверка item_id для загрузки/удаления картинок
     * @param int $id
     * @return Brevis\Model\Item or false
     */
    protected function _checkItemID($id) {
        if ($item = $this->core->xpdo->getObject('Brevis\Model\Item', $id)) {
            // проверим, что склад принадлежит пользователю
            if (array_key_exists($item->sklad_id, $this->sklads)) {
                return $item;
            }
        }
        return false;
    }
}

/**
 * Обработка ajax-загрузки одного файла
 */
Class Uploadone extends Images {
    public function run() { 
        // проверить наличие и корректность item_id
        if (!empty($_REQUEST['item_id']) and $item = $this->_checkItemID($_REQUEST['item_id']) and !empty($_FILES["files"])) {
            if ($_FILES["files"]["error"][0] == UPLOAD_ERR_OK) {
                $target = str_replace('//', '/', PROJECT_ASSETS_PATH . basename($_FILES['files']['tmp_name'][0]));
//                $name = basename($_FILES["pictures"]["name"][$key]);
                if (move_uploaded_file($_FILES['files']['tmp_name'][0], $target)) {
                    $tg = new Tgenerator($this->core);
                    if ($filename = $tg->processUploadedFile($target, $item->prefix)) {
                        // создать объект ItemImages
                        $itemImages = $this->core->xpdo->newObject('Brevis\Model\ItemImages', [
                            'item_id' => $item->id,
                            'item_key' => $item->code,
                            'prefix' => $item->prefix,
                            'filename' => $filename,
                            'hash' => $tg->getHash(),
                        ]);
                    $itemImages->save();
                        $this->eventLogger->newPhoto($item->id, PROJECT_ASSETS_URL.'images/data/'.$item->prefix.'/'.$filename);
                        // отправляем на модерацию
                        if (!$this->isManager) {
                            $item->set('moderate', 0);
                            $item->save();
                        }
                        // Обновляем время склада
                        $sklad = $item->getOne('Sklad');
                        $sklad->updateTime();
                    } else {
                        $error = $tg->getError();
                    }
                } else {
                    $error = 'move_uploaded_file error';
                }
            } else {
                $error = $_FILES["files"]["error"][0];
            }
        } else {
            $error = 'Access denied';
        }
        if (!empty($error)) {
            die(json_encode(['error' => $error]));
        } else {
            die(json_encode([
                    'filename' => $filename, 
                    'prefix' => $item->prefix,
                    'item_id' => $item->id,
                    'id' => $itemImages->id,
                ]));
        }
    }
}

class Removeimage extends Images {
    
    public function run() {
        $counter = 0;
        if (!empty($_REQUEST['item_id']) and $item = $this->_checkItemID($_REQUEST['item_id'])) {
            if (!empty($_REQUEST['id'])) {
                // одна картинка
                if ($image = $item->getOne('Images', $_REQUEST['id'])) {
                    if ($image->remove()) {
                        $counter++;
                        $this->success = true;
                        $this->message = 'Удалено';
                    } else {
                        $this->message = 'Can\'t remove Image';
                    }
                } else {
                    $this->message = 'Can\'t getOne Images';
                }
            } else {
                // все картинки
                if ($images = $item->getMany('Images')) {
                    $this->success = true;
                    $this->message = 'Удалено';
                    foreach ($images as $image) {
                        if (!$image->remove()) {
                            $this->success = false;
                            $this->message = 'Can\'t remove Image '.$image->id;
                        } else {
                            $counter++;
                        }
                    }
                } else {
                    $this->message = 'Can\'t getMany Images';
                }
            }
            // снимаем с публикации позицию без фото
            // empty($item->getMany('Images'))) return false
            if (empty($this->core->xpdo->getCount('Brevis\Model\ItemImages', $item->id))) {
                $item->set('published', 0);
                $item->save();
            }
        } else {
            $this->core->sendErrorPage(403);
        }
        if ($this->success) {
            $this->eventLogger->removePhoto($_REQUEST['item_id'], $counter);
            // Обновляем время склада
            $sklad = $item->getOne('Sklad');
            $sklad->updateTime();
        }
        
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            die($this->success ? 'OK' : $this->message);
        }
    }
}

/*
 * Сортировка изображений
 */
class Sort extends Images {
    
    public function run() {
        if (!empty($_REQUEST['item_id']) and $item = $this->_checkItemID($_REQUEST['item_id'])) {
            if (!empty($_REQUEST['order'])) {
                // новый порядок
                $newOrder = explode(',', $_REQUEST['order']);
                // старый порядок
                $oldOrder = $this->_getOrder($item);
                // если массивы не равны и одинаковой длины
                if (is_array($oldOrder) and $newOrder !== $oldOrder and count($newOrder) === count($oldOrder)) {
                    $isChanged = false; // есть изменения
                    foreach ($newOrder as $order => $id) {
                        if ($id != $oldOrder[$order]) {
                            $this->_setOrder($id, $order);
                            $isChanged = true;
                        }
                    }
                    if ($isChanged) {
                        $this->success = true;
                        $this->message = 'Сохранено';
                    }
                }
            }
        } else {
            $this->core->sendErrorPage(403);
        }
        
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message);
        } else {
            die($this->success ? 'OK' : $this->message);
        }
    }
    
    /**
     * Получает массив с порядком сортировки картинок
     * для позиции
     * @param Brevis\Model\Item $item
     * @return array order=>id or false on error
     */
    private function _getOrder($item) {
        $c = $this->core->xpdo->newQuery('Brevis\Model\ItemImages', ['item_id' => $item->id]);
        $c->select('id');
        $c->sortby('`order`');
        if ($c->prepare() and $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            $res = [];
            foreach ($rows as $row) {
                $res[] = $row['id'];
            }
            return $res;
        } else {
            $this->core->log('Не могу выбрать element:' . print_r($c->stmt->errorInfo(), true));
            return false;
        }
    }
    
    /**
     * Устанавливает новый order для картинки
     * @param int $imageId
     * @param int $order
     */
    private function _setOrder($imageId, $order) {
        if ($image = $this->core->xpdo->getObject('Brevis\Model\ItemImages', $imageId)) {
            $image->set('order', $order);
            $image->save();
        }
    }
}

/**
 * Печать стикера
 */
class Sticker extends Items {
    
    public $name = 'Печать стикера';
    public $template = 'items.sticker';

    public function run() {
        if (!empty($_REQUEST['id']) and $item = $this->core->xpdo->getObject('Brevis\Model\Item', $_REQUEST['id'])) {
            $barcodeGenerator = new BarcodeGenerator;
            $data = [
                'item' => $item,
                'barcodeBase64' => base64_encode($barcodeGenerator->getBarcode($item->code, $barcodeGenerator::TYPE_CODE_128)),
            ];
            return $this->template($this->template, $data, $this);
        } else {
            $this->core->sendErrorPage();
        }
    }
}

/**
 * Обработка запроса на добавление марки/модели в каталог
 */
class RequestCar extends Items {
    public function run() {
        $errors = [];
        $form = $this->_formRequestCar();
        if ($form->process() and !$form->hasError()) {
            $fields = $form->getValues();
            if (!empty($fields['mark_input'])) {
                $fields['mark'] = $fields['mark_input'];
            } else {
                $mark = $this->core->xpdo->getObject('Brevis\Model\Cars', ['mark_key' => $fields['mark_select']]);
                $fields['mark'] = $mark->mark_name;
            }
            if ($this->_sendRequestMail($fields)) {
                $this->success = true;
                $this->message = 'Ваше предложение отправлено';
            } else {
                $this->message = 'Возникла ошибка при попытке отправить почту. Пожалуйста, сообщите администратору.';                
            }
        } else {
            $errors = $form->getErrors();
            $this->message = 'Пожалуйста, исправьте ошибки в форме';
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors]);
        } else {
            die($this->message);
        }
    }
}

/**
 * Обработка запроса на добавление марки/модели в каталог
 */
class RequestCategory extends Items {
    public function run() {
        $errors = [];
        $form = $this->_formRequestCategory();
        if ($form->process() and !$form->hasError()) {
            $fields = $form->getValues();
            if (!empty($fields['category_input'])) {
                $fields['category'] = $fields['category_input'];
            } else {
                $category = $this->core->xpdo->getObject('Brevis\Model\Category', ['key' => $fields['category_select']]);
                $fields['category'] = $category->name;
            }
            if ($this->_sendRequestMail($fields)) {
                $this->success = true;
                $this->message = 'Ваше предложение отправлено';
            } else {
                $this->message = 'Возникла ошибка при попытке отправить почту. Пожалуйста, сообщите администратору.';                
            }
        } else {
            $errors = $form->getErrors();
            $this->message = 'Пожалуйста, исправьте ошибки в форме';
        }
        if ($this->isAjax) {
            $this->core->ajaxResponse($this->success, $this->message, ['errors' => $errors]);
        } else {
            die($this->message);
        }
    }
}