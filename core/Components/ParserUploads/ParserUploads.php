<?php
    
    namespace Brevis\Components\ParserUploads;
    
    use \xPDO\xPDO as xPDO;
    use \Exception as Exception;
    use XMLReader;
    
    class AbstractXMLReader {

        protected $reader;
        protected $result = array();
        // события
        protected $_eventStack = array();

        /*
          Конструктор класса.
          Создает сущность XMLReader и загружает xml, либо бросает исключение
         */

        public function __construct($xml_path) {
            $this->reader = new XMLReader();
            if (is_file($xml_path))
                $this->reader->open($xml_path);
            else
                throw new Exception('XML file {' . $xml_path . '} not exists!');
        }
        
        public function __destruct() {
            $this->clearResult();
            $this->reader->close();
        }

        /*
          Получить результаты парсинга
         */

        public function getResult() {
            return $this->result;
        }

        /*
          Очистить результаты парсинга
         */

        public function clearResult() {
            $this->result = array();
        }

        /*
          Вызывается при каждом распознавании
         */

        public function onEvent($event, $callback) {
            if (!isset($this->_eventStack[$event])) {
                $this->_eventStack[$event] = array();
            }
            $this->_eventStack[$event][] = $callback;
            return $this;
        }

        /*
          Выстреливает событие
         */

        public function fireEvent($event, $params = null, $once = false) {
            if ($params == null)
                $params = array();
            $params['context'] = $this;
            if (!isset($this->_eventStack[$event]))
                return false;
            $count = count($this->_eventStack[$event]);
            if ($count > 0) {
                for ($i = 0; $i < $count; $i++) {
                    call_user_func_array($this->_eventStack[$event][$i], $params);
                    if ($once == true) {
                        array_splice($this->_eventStack[$event], $i, 1);
                    }
                }
            }
        }

        /*
          Потоково парсит xml и вызывает методы для определенных элементов
          напр.
          при обнаружении элемента <Rubric> попытается вызвать метод parseRubric
          все методы парсинга должны быть public или protected.
         */

        public function parse() {
            $this->reader->read();
            while ($this->reader->read()) {
                if ($this->reader->nodeType == XMLREADER::ELEMENT) {
                    $fnName = 'parse' . $this->reader->localName;
                    if (method_exists($this, $fnName)) {
                        $lcn = $this->reader->name;
                        // стреляем по началу парсинга блока
                        $this->fireEvent('beforeParseContainer', array('name' => $lcn));
                        // пробежка по детям
                        if ($this->reader->name == $lcn && $this->reader->nodeType != XMLREADER::END_ELEMENT) {
                            // стреляем событие до парсинга элемента
                            $this->fireEvent('beforeParseElement', array('name' => $lcn));
                            // вызываем функцию парсинга
                            $this->{$fnName}();
                            // стреляем событием по названию элемента
                            $this->fireEvent($fnName);
                            // стреляем событием по окончанию парсинга элемента
                            $this->fireEvent('afterParseElement', array('name' => $lcn));
                        } elseif ($this->reader->nodeType == XMLREADER::END_ELEMENT) {
                            // стреляем по окончанию парсинга блока
                            $this->fireEvent('afterParseContainer', array('name' => $lcn));
                        }
                    }
                } 
//                elseif ($this->reader->nodeType == XMLREADER::END_ELEMENT) {
//                    // стреляем по окончанию парсинга блока
//                    $this->fireEvent('afterParseContainer', array('name' => $this->reader->name));
//                }
            }
        }

    }
    
    class ParserUploads extends AbstractXMLReader {
        
        /** 
        * @var string path
        * Путь к файлам с данными
        */
        public $path = 'data/', 
               $imgPath = 'images/data/';
        
        public $countItem = 0, $countImg = 0;

        /**
         * @var \Brevis\Model\Sklad $sklad
         */
        public $sklad = null;
        public $id = null;
        
        // массивы для экранирования строк
        public $escapeSearch = array("'"), $escapeReplace = array("\'");
        
        public $log_level = 'ERROR'; // INFO || ERROR
        public $log_target = 'MAIL'; // FILE || MAIL
        public $logger;
        
        function __construct($Core, \Brevis\Model\Sklad $sklad, $filename, $config = []) {
            $this->core = $Core;
            $this->xPDO = $Core->xpdo;
            if (!empty($config)) {
                $this->readConfig($config);
            }
            $this->path = PROJECT_ASSETS_PATH . $this->path;
            $this->imgPath = PROJECT_ASSETS_PATH . $this->imgPath;
            $this->sklad = $sklad;
            // logger
            $this->logger = $this->core->newLogger('ParserUploads', $this->log_target, $this->log_level);
            parent::__construct($filename);
        }
        
        private function readConfig(array $config) {
            $properties = get_class_vars(static::class);
            foreach ($properties as $property => $value) {
                if (isset($config[$property])) {
                    $this->$property = $config[$property];
                }
            }
        }
        
        protected function parsePrefiks() {
//            if ($this->reader->nodeType == XMLREADER::ELEMENT && $this->reader->localName == 'Prefiks') {
//                $this->prefix = $this->prefixes[$this->reader->getAttribute('Prefiks')];
//            }
        }
        
        protected function parsetovar() {
            if ($this->reader->nodeType == XMLREADER::ELEMENT && $this->reader->localName == 'tovar') {
                // объект для сохранения
                $tovar = $this->parseCode((string)$this->reader->getAttribute('kode'));
                $tovar['code'] = $this->reader->getAttribute('kode');
                $tovar['vendor_code'] = $this->reader->getAttribute('art');
                $tovar['condition'] = (int)$this->reader->getAttribute('condition');
                $tovar['comment'] = $this->reader->getAttribute('comment');
                $tovar['name'] = $this->escapeString((string)$this->reader->getAttribute('name'));
                $tovar['kol'] = (int)$this->reader->getAttribute('kol');
                $tovar['price'] = (int)$this->reader->getAttribute('price');
                $tovar['prefix'] = $this->sklad->prefix;
                $tovar['sklad_id'] = $this->sklad->id;
                $tovar['supplier_id'] = $this->sklad->supplier_id;
                $tovar['moderate'] = 1;
                $tovar['published'] = 1;
                $tovar['source'] = '1C';
                $tovar['body_type'] = (int)$this->reader->getAttribute('body_type');
                $this->result['tovar'][] = $tovar;
            }
        }
        
        protected function parsepicture() {
            if ($this->reader->nodeType == XMLREADER::ELEMENT && $this->reader->localName == 'picture') {
                $this->result['pictures'][] = $this->reader->getAttribute('binary');
            }
        }
        protected function parsepicture_list() {
            
        }
        
        /**
        * AEB1A014A0200001
        * 2 символа – марка
        * 3 символа – модель
        * 2 символа – года выпуска
        * 1 символ  – категория
        * 4 символа – список деталей
        * 4 символа – счетчик
        * 
        * @param string $str
        * @return array
        */
        public function parseCode($str) {
            $code['mark_key'] = substr($str,0,2);
            $code['model_key'] = substr($str,2,3);
            $code['year_key'] = substr($str,5,2);
            $code['category_key'] = substr($str,7,1);
            $code['element_key'] = substr($str,8,4);
            $code['counter'] = substr($str,-4);
            return $code;
        }

        private function escapeString($str) {
            return str_replace($this->escapeSearch, $this->escapeReplace, $str);
        }

        /**
         * Очистка/создание директории для картинок
         * @param bool $remove (optional) Очищать или нет директорию, default true
        */
        public function prepareImgDir($remove = true) {
            $path = $this->imgPath.$this->sklad->prefix;
            if (is_dir($path)) {
                if ($remove) {
                    $this->core->rmDir($path);
                }
            } else {
                mkdir($path);
            }
        }
        
        public function clearPictures() {
            $this->result['pictures'] = array();
        }
        
    }