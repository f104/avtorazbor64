<?php

namespace Brevis\Components\EventLogger;

use \xPDO\xPDO as xPDO;

class EventLogger {
    
    /** @var Core $core */
    public $core;
    
    /** @var array $lang */
    public $lang;
    
    /** @var array $statuses Для замены id статусов на названия */
    public $statuses;
    
    public $langPrefix = '';
    
    private $_category;

    function __construct($core, $lang, $category, $statuses = []) {
        $this->core = $core;
        $this->lang = $lang;
        $this->statuses = $statuses;
        $this->_category = $category;
    }
    
    /**
     * Добавление записи о создании
     * @param int $id ID созданного объекта
     */
    public function add($id) {
        $this->_write($id, 'Создание');
    }
    
    /**
     * Добавление записи об удалении
     * @param int $id ID удаленного объекта
     */
    public function remove($id) {
        $this->_write($id, 'Удаление');
    }
    
    /**
     * Добавление записи об изменении
     * @param int $id ID измененного объекта
     * @param array $old Старые значения
     * @param array $new Новые значения
     */
    public function update($id, $old, $new) {
        $res = [];
        unset($old['hash'], $old['updatedon']);
        foreach ($old as $k => $v) {
            if (isset($new[$k]) and $new[$k] != $v) {
                $res[] = $this->lang[$this->langPrefix.$k] . ': ' . $this->_format($k, $v) . ' &rarr; ' . $this->_format($k, $new[$k]);
            }
        }
        if (!empty($res)) {
            $this->_write($id, $res);
        }
    }
    
    /**
     * Добавление записи о новом фото товара
     * @param int $id ID измененного объекта
     * @param string $path Путь к фото
     */
    public function newPhoto($id, $path) {
        $this->_write($id, '<a href="'.$path.'" target="_blank">Новое фото</a>');
    }
    
    /**
     * Добавление записи об удалении фото товара
     * @param int $id ID измененного объекта
     * @param int $count Кол-во удаленных фото
     */
    public function removePhoto($id, $count) {
        $this->_write($id, 'Удалено фото: '.$count);
    }
    
    /**
     * Форматирование значения, в зависимости от ключа
     * @param string $k
     * @param string $v
     * @return string
     */
    private function _format($k, $v) {
        switch ($k) {
            case 'switchon':
            case 'active':
            case 'blocked':
            case 'published':
                $v = $v == 0 ? $this->lang['no'] : $this->lang['yes'];
                break;
            case 'moderate':
                switch ($v) {
                    case -1: $v = $this->lang['item.moderate.no']; break;
                    case 0: $v = $this->lang['item.moderate.wait']; break;
                    case 1: $v = $this->lang['item.moderate.yes']; break;
                }
                break;
            case 'status_id':
            case 'type_id':
                $v = $this->statuses[$v];
                break;
            case 'passhash':
                $v = '***';
                break;
        }
        return $v;
    }
    
    /**
     * Пишет данные в базу
     * @param int $id
     * @param string||array $message
     */
    private function _write($id, $message) {
        if (is_array($message)) {
            $message = implode('<br>', $message);
        }
        $event = $this->core->xpdo->newObject('Brevis\Model\EventLog', [
            'user_id' => $this->core->authUser->id,
            'category' => $this->_category,
            'subject_id' => $id,
            'message' => $message,
        ]);
        $event->save();
    }

}
