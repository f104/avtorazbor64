<?php
    
    // @TODO рефакторинг!

define('PROJECT_API_MODE', true);
use \xPDO\xPDO as xPDO;
//use \Brevis\Model\mysql\Category as Category;
$base = dirname(dirname(__FILE__)) . '/';
require_once $base . 'index.php';
        
class aParser {
    
    private $images = array();


    /** 
     *
     * @var string path
     * Путь к файлам с данными
     */
    public $path, $imgPath;
    
    /**
     *
     * @var array imgThumbs
     * Размеры превьюшек для галереи
     * ['120x90', '800x600']
     * default 120x90
     */
    public $imgThumbs = array('120x90');
    
    /**
     *
     * @var string
     * Maximum image size (WxH)
     * Default 1024x1024
     */
    public $imgSize= '1024x1024';


    public $xslx = array(
        'category' => array(
            'class' => 'Brevis\Model\Category',
            'sheet' => 2,
            'columns' => array('A','B'),
            'skip' => 0,
        ),
        'element' => array(
            'class' => 'Brevis\Model\Element',
            'sheet' => 3,
            'columns' => array('A','B','C','F'),
            'skip' => 0,
        ),
        'cars' => array(
            'class' => 'Brevis\Model\Cars',
            'sheet' => 1,
            'columns' => array('A','B','C','D','E','F'),
            'skip' => 1,
        ),
    );
    public $xml = array(
        'Выгрузка_остатков_МС.xml',
    );
    
    // массивы для экранирования строк
    public $escapeSearch = array("'"), $escapeReplace = array("\'");

    /** @var Array $sharedStrings */
    private $sharedStrings;
    /** @var xPDO $xPDO */
    private $xPDO;
    
    function __construct($Core) {
        $this->xPDO = $Core->xpdo;
        $this->path = PROJECT_ASSETS_PATH.'data/';
        $this->imgPath = PROJECT_ASSETS_PATH.'images/data/';
    }
    
    public function runXLSX() {
        $this->parseSharedStrings();
        if (empty($this->sharedStrings)) {
            $this->log('Ошибка разбора sharedStrings');
        } else {
            foreach ($this->xslx as $key => $sheet) {
                if ($data = $this->parseXSLXsheet($sheet['sheet'], $sheet['columns'], $sheet['skip'])) {
                    $count = $this->writeData($sheet['class'], $data);
                    $this->log('Добавлено данных '.$key.': '.$count);                
                } else {
                    $this->log('Ошибка разбора файла данных '.$key);
                }
            }
        }
    }
    
    public function runXML() {
        
        // prepare imgs&thumbs sizes: 0 - width, 1 - height; owerwrite repeates
        $this->imgSize = explode('x', $this->imgSize);
        $thumbs = array();
        foreach ($this->imgThumbs as $item) {
            $thumbs[$this->imgPath.'/'.$item.'/'] = explode('x', $item); // key is path
        }
        $this->imgThumbs = $thumbs;
        
        // prepare imgs dir
        $this->clearDir($this->imgPath);
        foreach ($this->imgThumbs as $path => $size) {
            $this->createThumbsDir($path);
        }
        
        $data = array();
        foreach ($this->xml as $file) {
            if ($dataFile = $this->parseXML($file)) {
                $this->log('Прочитано данных из файла '.$file.': '.count($dataFile));
                $data = array_merge($data, $dataFile);
            } else {
                $this->log('Ошибка разбора файла данных '.$file);
            }
        }
        $count = $this->writeData('Brevis\Model\Item',$data);
        $this->log('Записано данных: '.$count);
        
        // insert ItemImages
        if (!empty($this->images)) {
            $count = $this->writeData('Brevis\Model\ItemImages',$this->images);
            $this->log('Записано данных об изображениях: '.$count);
        }
        
    }
    
    

    private function parseSharedStrings() {
        if ($xml = $this->loadXMLfile('sharedStrings.xml')) {
            foreach ($xml->children() as $item) {
                $this->sharedStrings[] = (string)$item->t;
            }            
        }
    }
    
    //<c r="A6" s="6" t="s"><v>2</v></c>
    public function parseXSLXsheet($sheet, $col, $skip = 0) {
        if ($xml = $this->loadXMLfile('sheet'.$sheet.'.xml')) {
            $data = array();
            $row = 1;
            foreach ($xml->sheetData->row as $item) {
                if ($row > $skip) {
                    $data[$row] = array();
                    $colTemp = array_flip($col); // защита от пропущенных данных
                    $cell = 1;
                    foreach ($item as $child) {
                        $attr = $child->attributes();
                        $c = substr($attr['r'],0,1); // название колонки
                        if (in_array($c, $col)) {
                            $value = isset($child->v) ? (string)$child->v : '';
                            if (isset($attr['t']) and $attr['t'] == 's') {
                                $data[$row][$c] = $this->sharedStrings[$value];
                            } else {
                                $data[$row][$c] = $value;
                            }
                            $data[$row][$c] = $this->escapeString($data[$row][$c]);
                            unset($colTemp[$c]);
                        }
                        $cell++;
                    }
                    foreach ($colTemp as $c) {
                        $data[$row][$c] = '';
                    }
                }
                $row++;
            } 
            return $data;
        } else {
            return FALSE;
        }
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
        $code['mcounter'] = substr($str,-4);
        return $code;
    }
    
    /*
     * <tovar kode="AEB1A014A0200001" name="Бампер задний Ford Focus III седан 2011 >" kol="1" price="6000" summa="6000">
     * <picture_list>
     * <picture binary=""/>
     */
    public function parseXML($file) {
        if ($xml = $this->loadXMLfile($file)) {
            $data = array();
            foreach ($xml->tovar as $item) {
                $row = $this->parseCode((string)$item['kode']);
                $row['name'] = $this->escapeString((string)$item['name']);
                $row['kol'] = (int)$item['kol'];
                $row['price'] = (int)$item['price'];
                $this->createImg($item);
                $data[] = $row;
            } 
            return $data;
        } else {
            return FALSE;
        }
    }
    
    private function createImg(SimpleXMLElement $item) {
        if (empty($item->picture_list)) {
            return;
        }
        $idx = 0;
        foreach ($item->picture_list->picture as $picture) {
            $binary = $picture['binary'];

            $imBlob = base64_decode($binary);

            $im = new Imagick();
            $im->readImageBlob($imBlob);
            $im->setImageFormat("jpeg");

            $im->scaleImage($this->imgSize[0], $this->imgSize[1], true);
            
            $code = (string)$item['kode'];
            $fileName = $code.'_'.$idx.'.jpg';

            // fullsize
            $im->writeImage($this->imgPath.$fileName); 
            
            // thumbs
            foreach ($this->imgThumbs as $path => $size) {
                $imThumb = $im;
                $imThumb->scaleImage($size[0], $size[1], true);
                $im->writeImage($path.$fileName);
                $imThumb->destroy();
            }

            $im->destroy();
            
            $this->images[] = array($code, $fileName);

            $idx++;
        }
        $this->log('Создано иллюстраций: '.$idx++);
    }

    private function writeData($class, $data) {
        $values = array();
        foreach ($data as $item) {
            $values[] = "('".implode("','", $item)."')";
        }
        $values = implode(',', $values);

        $table = $this->xPDO->getTableName($class);
        $fields = $this->xPDO->getFields($class);
        unset($fields[$this->xPDO->getPK($class)]);
        $fields = array_keys($fields);
        foreach ($fields as &$item) {
            $item = $this->xPDO->escape($item);
        }
        $fields = implode(',', $fields);
        
        $this->xPDO->exec("TRUNCATE $table");
        $sql = "INSERT INTO $table ($fields) values $values";
        return $this->xPDO->exec($sql);
    }
    
    /**
     * Truncate table
     * @param string $class
     */
    private function truncateTable($class) {
        $table = $this->xPDO->getTableName($class);
        return $this->xPDO->exec("TRUNCATE $table");
    }
    
    private function escapeString($str) {
        return str_replace($this->escapeSearch, $this->escapeReplace, $str);
    }

    public function log($msg) {
        $this->xPDO->log(xPDO::LOG_LEVEL_INFO, $msg);
    }
    
    private function loadXMLfile($file) {
        $file = $this->path.$file;
        if (file_exists($file)) {
            return simplexml_load_file($file);
        } else {
            $this->log('Не найден файл '.$file);
            return FALSE;
        }
    }
    
    /**
     * Создает директорию $path
     * @param string $path
     * @return bool
     */
    private function createThumbsDir($path) {
        return mkdir($path);
    }
    
    /**
     * Recursive remove directories and files into $path
     * @param string $path
     */
    private function clearDir($path) {
        foreach (new DirectoryIterator($path) as $fileName => $fileInfo) {
            if(!$fileInfo->isDot()) {
                if($fileInfo->isDir()) {
                    $this->clearDir($path.$fileInfo->getFilename());
                    rmdir($fileInfo->getPathname());
                } else {
                    unlink($fileInfo->getPathname());
                }
            } 
        }
    }
}

$parser = new aParser($Core);
//$parser->runXLSX();
$parser->runXLSX();
//$parser->clearDir('/var/www/genuine/www/assets/images/data/');
$parser->log(memory_get_usage());