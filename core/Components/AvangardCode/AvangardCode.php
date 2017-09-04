<?php

/**
 * Генерация кодов для каталога
 */

namespace Brevis\Components\AvangardCode;
use Brevis\Components\Component as Component;

class AvangardCode extends Component {

    /** @var string Имя класса модели */
    public $classname = 'Brevis\Model\Cars';
    
    /**
     * Максимальный ключ
     * @param string $column Столбец, для которого ищем максимум
     * @param string $mark_key
     * @param string $model_key
     * @return boolean|string false on error
     */
    public function getLastKey($column, $mark_key = null, $model_key = null) {
        $c = $this->core->xpdo->newQuery($this->classname);
        $c->select("MAX($column)");
        $where = [];
        if (!empty($mark_key)) {
            $where['mark_key:='] = $mark_key;
        }
        if (!empty($model_key)) {
            $where['model_key:='] = $model_key;
            $c->sortby("LPAD(`$column`, 2, '0')", 'DESC'); // иначе неверно отсортирует
        }
        if (!empty($where)) {
            $c->where($where);
        }
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() and $c->stmt->execute()) {
            $max = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            return $max[0];
        } else {
            $this->_error = 'Не могу выбрать ' . $this->classname . ':' . print_r($c->stmt->errorInfo(), true);
            return false;
        }
    }
    
    public function getLastYearKey($mark_key = null, $model_key = null) {
        $c = $this->core->xpdo->newQuery($this->classname);
        $where = [];
        $where['mark_key:='] = $mark_key;
        $where['model_key:='] = $model_key;
        $c->sortby("LPAD(`year_key`, 2, '0')", 'DESC'); // иначе неверно отсортирует
        $c->select("year_key");
        $c->where($where);
//        $c->prepare(); die($c->toSQL());
        if ($c->prepare() and $c->stmt->execute()) {
            $max = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            return isset($max[0]) ? $max[0] : null;
        } else {
            $this->_error = 'Не могу выбрать ' . $this->classname . ':' . print_r($c->stmt->errorInfo(), true);
            return false;
        }
    }
    
    /**
     * Максимальный ключ категории
     * @return boolean|string false on error
     */
    public function getLastCategoryKey() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Category');
        $c->select("MAX(`key`)");
        if ($c->prepare() and $c->stmt->execute()) {
            $max = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            return $max[0];
        } else {
            $this->_error = 'Не могу выбрать Brevis\Model\Category:' . print_r($c->stmt->errorInfo(), true);
            return false;
        }
    }
    
    /**
     * Максимальный ключ элемента
     * @return boolean|string false on error
     */
    public function getLastElementKey() {
        $c = $this->core->xpdo->newQuery('Brevis\Model\Element');
        $c->select("MAX(`key`)");
        if ($c->prepare() and $c->stmt->execute()) {
            $max = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            return $max[0];
        } else {
            $this->_error = 'Не могу выбрать Brevis\Model\Element:' . print_r($c->stmt->errorInfo(), true);
            return false;
        }
    }
    
    /**
     * Генерация следующего кода марки
     * A1 ... A9 AB ... AZ B1 ... BZ ... ZZ
     * 'A'++ = 'B'
     * '1'++ = 2
     * @return string or false on error
     */
    public function getNextMarkKey() {
        if ($lastKey = $this->getLastKey('mark_key')) {
            if ($lastKey == 'ZZ') {
                $this->_error = 'Достигнут максимум для кодов марок (ZZ)';
            } else {
                $first = $lastKey[0];
                $second = $lastKey[1];
                if (intval($second) == 0) {
                    // это буква
                    if ($second == 'Z') {
                        // конец алфавита, увеличиваем первую, вторая - 1
                        $first++;
                        $second = '1';
                    } else {
                        // увеличиваем вторую
                        $second++;
                    }
                } else {
                    // это цифра
                    if ($second == '9') {
                        $second = 'A';
                    } else {
                        $second++;
                    }
                }
                return $first . $second;
            }
        }
        return false;
    }
    
    /**
     * Генерация следующего кода модели
     * A11 ... A19 A1A ... A1Z B11 ... B1Z ... Z1Z
     * 'A'++ = 'B'
     * '1'++ = 2
     * @param string $mark_key
     * @return string or false on error
     */
    public function getNextModelKey($mark_key) {
        $lastKey = $this->getLastKey('model_key', $mark_key);
        if ($lastKey !== false) {
            if (empty($lastKey)) {
                return 'A11';
            }
            if ($lastKey == 'Z1Z') {
                $this->_error = 'Достигнут максимум для кодов моделей (Z1Z)';
            } else {
                $first = $lastKey[0];
                $second = $lastKey[1]; // always 1
                $third = $lastKey[2];
                if (intval($third) == 0) {
                    // это буква
                    if ($third == 'Z') {
                        // конец алфавита, увеличиваем первую, третья - 1
                        $first++;
                        $third = '1';
                    } else {
                        // увеличиваем третью
                        $third++;
                    }
                } else {
                    // это цифра
                    if ($third == '9') {
                        $third = 'A';
                    } else {
                        $third++;
                    }
                }
                return $first . $second . $third;
            }
        }
        return false;
    }
    
    /**
     * Генерация следующего кода поколения
     * 1 ... 99 9A ... 9Z
     * 'A'++ = 'B'
     * '1'++ = 2
     * @param string $mark_key
     * @param string $model_key
     * @return string or false on error
     */
    public function getNextYearKey($mark_key, $model_key) {
        $lastKey = $this->getLastYearKey($mark_key, $model_key);
        if ($lastKey !== false) {
            if (empty($lastKey)) {
                return '1';
            }
            if ($lastKey == '9Z') {
                $this->_error = 'Достигнут максимум для кодов поколений (9Z)';
            } else {
                if (strlen($lastKey) == 1) {
                    // 1..9
                    return ++$lastKey;
                } else {
                    if (intval($lastKey) < 100) {
                        // 10..99
                        return $lastKey != '99' ? ++$lastKey : '9A';
                    } else {
                        // 9A..9Y
                        $first = $lastKey[0]; // цифра 9
                        $second = $lastKey[1]; // буква
                        $second++;
                        return $first . $second;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * Генерация следующего кода категории
     * 1 ... 9 A ... Z
     * @return string or false on error
     */
    public function getNextCategoryKey() {
        $lastKey = $this->getLastCategoryKey();
        if ($lastKey !== false) {
            if (empty($lastKey)) {
                return '1';
            }
            if ($lastKey == 'Z') {
                $this->_error = 'Достигнут максимум для кодов категорий (Z)';
            } else {
                return $lastKey == '9' ? 'A' : ++$lastKey;
            }
        }
        return false;
    }
    
    /**
     * Генерация следующего кода элемента
     * A001 ... A999 B001 ... Z999
     * A001++ == A002 
     * A999++ == B000
     * @return string or false on error
     */
    public function getNextElementKey() {
        $lastKey = $this->getLastElementKey();
        if ($lastKey !== false) {
            if (empty($lastKey)) {
                return 'A001';
            }
            if ($lastKey == 'Z999') {
                $this->_error = 'Достигнут максимум для кодов элементов (Z999)';
            } else {
                $lastKey++;
                if (substr($lastKey, -3) == '000') {
                    $lastKey++;
                }
                return $lastKey;
            }
        }
        return false;
    }
    
}