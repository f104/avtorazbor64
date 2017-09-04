<?php

/**
 * Обновление каталога с родительского сайта
 */

namespace Brevis\Components\ImportCatalog;
use Brevis\Components\Component as Component;

class ImportCatalog extends Component {
    
    /** @var array Типы получаемых данных */
    private $_gets = ['increases', 'bodytypes', 'conditions', 'elements', 'categories', 'cars'];
    /** @var string Uri для запроса данных */
    public $uri = null;
    /** @var string Email пользователя API */
    public $email = null;
    /** @var string Ключ пользователя API */
    public $key = null;
    
    public function __construct($core, $config = array()) {
        parent::__construct($core, $config);
        $this->_loadConfig();
    }
    
    private function _loadConfig() {
        $filename = __DIR__ . '/config.inc.php';
        if (file_exists($filename)) {
            include $filename;
            $this->email = $email;
            $this->key = $key;
            $this->uri = $uri;
        }
    }
    
    private function _getRemoteData($get) {
        $data = file_get_contents($this->uri . '?' . http_build_query([
            'email' => $this->email,
            'key' => $this->key,
            'get' => $get,
        ]));
        return $data;
    }
    
    public function run() {
        if ($this->hasError()) {
            exit($this->getError());
        }
        foreach ($this->_gets as $get) {
            $data = $this->_getRemoteData($get);
            if ($data !== false) {
                $data = new \SimpleXMLElement($data);
//                var_dump($data);
                $setFunction = '_set' . ucfirst($get);
                $this->$setFunction($data);
            }
        }
    }
    
    private function _truncateTable($table) {
        $sql = "TRUNCATE $table";
        $q = $this->core->xpdo->prepare($sql);
        $q->execute();
    }

    private function _setConditions($data) {
        $table = $this->core->xpdo->getTableName('Brevis\Model\Condition');
        $this->_truncateTable($table);
        $values = [];
        foreach ($data->item as $row) {
            $values[] = "({$this->core->xpdo->quote($row['id'])}, {$this->core->xpdo->quote($row['name'])})";
        }
        $sql = "INSERT INTO $table VALUES " . implode(',', $values);
        $q = $this->core->xpdo->prepare($sql);
        if ($q->execute()) {
            $this->logger->info("$table: {$data['total']}");
        } else {
            $this->logger->error("$table: " . print_r($q->errorInfo(), true));
        }
    }
    
    private function _setBodytypes($data) {
        $table = $this->core->xpdo->getTableName('Brevis\Model\BodyType');
        $this->_truncateTable($table);
        $values = [];
        foreach ($data->item as $row) {
            $values[] = "({$this->core->xpdo->quote($row['id'])}, {$this->core->xpdo->quote($row['name'])})";
        }
        $sql = "INSERT INTO $table VALUES " . implode(',', $values);
        $q = $this->core->xpdo->prepare($sql);
        if ($q->execute()) {
            $this->logger->info("$table: {$data['total']}");
        } else {
            $this->logger->error("$table: " . print_r($q->errorInfo(), true));
        }
    }
    
    private function _setIncreases($data) {
        $table = $this->core->xpdo->getTableName('Brevis\Model\Increase');
        $this->_truncateTable($table);
        $values = [];
        foreach ($data->item as $row) {
            $values[] = "({$this->core->xpdo->quote($row['id'])}, {$this->core->xpdo->quote($row['increase'])}, {$this->core->xpdo->quote($row['allow_remove'])})";
        }
        $sql = "INSERT INTO $table VALUES " . implode(',', $values);
        $q = $this->core->xpdo->prepare($sql);
        if ($q->execute()) {
            $this->logger->info("$table: {$data['total']}");
        } else {
            $this->logger->error("$table: " . print_r($q->errorInfo(), true));
        }
    }
    
    private function _setElements($data) {
        $table = $this->core->xpdo->getTableName('Brevis\Model\Element');
        $this->_truncateTable($table);
        $values = [];
        foreach ($data->item as $row) {
            $values[] = "({$this->core->xpdo->quote($row['category_key'])}, {$this->core->xpdo->quote($row['key'])}, {$this->core->xpdo->quote($row['name'])}, {$this->core->xpdo->quote($row['increase_category'])}, {$this->core->xpdo->quote($row['increase_category_id'])})";
        }
        $sql = "INSERT INTO $table (`category_key`, `key`, `name`, `increase_category`, `increase_category_id`) VALUES " . implode(',', $values);
        $q = $this->core->xpdo->prepare($sql);
        if ($q->execute()) {
            $this->logger->info("$table: {$data['total']}");
        } else {
            $this->logger->error("$table: " . print_r($q->errorInfo(), true));
        }
    }
    
    private function _setCategories($data) {
        $table = $this->core->xpdo->getTableName('Brevis\Model\Category');
        $this->_truncateTable($table);
        $values = [];
        foreach ($data->item as $row) {
            $values[] = "({$this->core->xpdo->quote($row['key'])}, {$this->core->xpdo->quote($row['name'])})";
        }
        $sql = "INSERT INTO $table (`key`, `name`) VALUES " . implode(',', $values);
        $q = $this->core->xpdo->prepare($sql);
        if ($q->execute()) {
            $this->logger->info("$table: {$data['total']}");
        } else {
            $this->logger->error("$table: " . print_r($q->errorInfo(), true));
        }
    }
    
    private function _setCars($data) {
        $table = $this->core->xpdo->getTableName('Brevis\Model\Cars');
        $this->_truncateTable($table);
        $values = [];
        foreach ($data->item as $row) {
            $values[] = "({$this->core->xpdo->quote($row['mark_key'])}, {$this->core->xpdo->quote($row['mark_name'])}, {$this->core->xpdo->quote($row['model_key'])}, {$this->core->xpdo->quote($row['model_name'])}, {$this->core->xpdo->quote($row['year_key'])}, {$this->core->xpdo->quote($row['year_name'])})";
        }
        $sql = "INSERT INTO $table (`mark_key`, `mark_name`, `model_key`, `model_name`, `year_key`, `year_name`) VALUES " . implode(',', $values);
        $q = $this->core->xpdo->prepare($sql);
        if ($q->execute()) {
            $this->logger->info("$table: {$data['total']}");
        } else {
            $this->logger->error("$table: " . print_r($q->errorInfo(), true));
        }
    }
    
}