<?php

namespace Brevis\Controllers;

use Brevis\Controller as Controller;
use Brevis\Components\Form\Form as Form;
use Brevis\Components\Form\FormFilters as FormFilters;

/**
 * Журнал действий
 */
class Eventlog extends Controller {
    
    public $name = 'Журнал действий';
    public $permissions = 'eventlog_view';
    public $langTopic = 'eventlog';
    
    public $limit = PROJECT_DEFAULT_QUERY_LIMIT;
    public $page = 1;
    private $_offset = 0;
    private $_total = 0;
    
    public $allowedFilters = ['user_id','category'];
    public $allowedSort = ['timestamp','user_id','category'];
    public $defaultSort = 'timestamp';
    public $sortdir = 'DESC';
    
    public $userSelect, $categorySelect;
    
    /**
     * @var array Список урл для перехода к просмотру субъекта 
     */
    public $urls = [
        'Товары' => 'items/view',
        'Пользователи' => 'users/view',
        'Поставщики' => 'suppliers/view',
        'Склады' => 'sklads/view',
        'Заказы' => 'orders/view',
        'Платежи' => 'payments/view',
        'Баланс' => 'fees/view',
    ];

    public function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        $c = $this->core->xpdo->newQuery('\Brevis\Model\EventLog');
        $c->select(['category']);
        $c->sortby('category');
        $c->distinct();
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->categorySelect[$row['category']] = $row['category'];
            }
        } else {
            $this->core->logger->error('Не могу выбрать EventLog:' . print_r($c->stmt->errorInfo(), true));
        }
        $c = $this->core->xpdo->newQuery('\Brevis\Model\EventLog');
        $c->innerJoin('Brevis\Model\User', 'User');
        $c->select(['EventLog.user_id', 'User.name', 'User.email']);
        $c->sortby('User.name');
        $c->distinct();
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->userSelect[$row['user_id']] = $row['name'].' ('.$row['email'].')';
            }
        } else {
            $this->core->logger->error('Не могу выбрать EventLog:' . print_r($c->stmt->errorInfo(), true));
        }
        $this->_readFilters($_GET);
    }
    
    public function run() {
        // фильтры
        $filters = new FormFilters([], $this);
        $filters->select('category')->addLabel($this->lang['category'])->setSelectOptions($this->categorySelect);
        $filters->select('user_id')->addLabel($this->lang['user_id'])->setSelectOptions($this->userSelect);
        $where = [];
        if (!empty($this->filters['category'])) {
            $where['category'] = $this->filters['category'];
        }
        if (!empty($this->filters['user_id'])) {
            $where['user_id'] = $this->filters['user_id'];
        }
        $cols = [
            'timestamp' => 
                ['title' => $this->lang['timestamp'], 'tpl' => '@INLINE {$row.timestamp|date:"d-m-Y H:i"}'],
            'user_id' => 
                ['title' => $this->lang['user_id'], 'tpl' => '@INLINE {$row.user_name} ({$row.user_email})'],
            'category' => 
                ['title' => $this->lang['category']],
            'subject_id' => 
                ['title' => $this->lang['subject_id'], 'sortable' => false, 'tpl' => '@INLINE <a href="{$row.url}">{$row.subject_id}</a>'],
            'message' => 
                ['title' => $this->lang['message'], 'sortable' => false],
        ];
        $rows = [];
        $c = $this->core->xpdo->newQuery('Brevis\Model\EventLog');
        $c->where($where);
        $this->_total = $this->core->xpdo->getCount('Brevis\Model\EventLog', $c);
        if ($this->page < 1 or $this->page > ceil($this->_total / $this->limit)) {
            $this->page = 1;
            $this->filters['page'] = 1;
        }
        $this->_offset = $this->limit * ($this->page - 1);
        $c->limit($this->limit, $this->_offset);
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\EventLog', 'EventLog'));
        $c->leftJoin('Brevis\Model\User', 'User');
        $c->select($this->core->xpdo->getSelectColumns('Brevis\Model\User', 'User', 'user_', ['name','email']));
        $c->sortby($this->sortby, $this->sortdir);
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() && $c->stmt->execute()) {
            $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
            // ссылки для просмотра
            foreach ($rows as &$item) {
                $item['url'] = $this->urls[$item['category']].'?id='.$item['subject_id'];
            }
        } else {
            $this->core->log('Не могу выбрать EventLog:' . print_r($c->stmt->errorInfo(), true));
        }
        $content = $this->template('_table', [
            'filters' => $filters->draw(),
            'cols' => $cols,
            'rows' => $rows,
            'total' => $this->_total,
            'offset' => $this->_offset,
            'pagination' => $this->getPagination($this->_total, $this->page, $this->limit),
        ], $this);
        if ($this->isAjax) {
            $this->core->ajaxResponse(true, '', ['html' => $content]);
        }
        $data = [
            'content' => $content,
        ];
        return $this->template($this->template, $data, $this);  
    }    
    
}