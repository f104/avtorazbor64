<?php
    
    /**
     * Генерация превьюшек для изображений
     */

    namespace Brevis\Components\Tgenerator;

    use \xPDO\xPDO as xPDO;
    use Imagick;
    
    class Tgenerator {
        
        /** 
        * @var string path
        * Путь к файлам
        */
        public $imgPath = 'images/data/';
        
        /**
         *
         * @var string имя файла для watermark
         */
        public $wmFilename = 'wm.png';
        
        /**
         *
         * @var bool использовать watermark
         */
        public $useWm = true;
        
        private $_wm = null; // imagick wm image

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
        public $imgSize = [
            'width' => 1024,
            'height' => 1024,
        ];
        
        /**
         *
         * @var int $limit Лимит выборки
         */
        public $limit = 100;
        
        // счетчики
        public $count = array(
            'read' => 0,
            'run' => 0,
            'write' => 0,
        );
        
        // список
        private $_sklads = array();
        
        public $log_level = 'ERROR'; // INFO || ERROR
        public $log_target = 'FILE'; // FILE || MAIL
        public $logger;
        
        private $_success = false;
        private $_error;
        
        private $_hash; // string hash file
            
        function __construct($core, $config = []) {
            $this->core = $core;
            if (!empty($config)) {
                $this->readConfig($config);
            }
            $this->imgPath = PROJECT_ASSETS_PATH . $this->imgPath;
            // prepare imgs&thumbs sizes: 0 - width, 1 - height; owerwrite repeates
            $thumbs = array();
            foreach ($this->imgThumbs as $item) {
                $path = $item.'/';
                $thumbs[$path] = explode('x', $item); // key is sub path
            }
            $this->imgThumbs = $thumbs;
            // logger
            $this->logger = $this->core->newLogger('Tgenerator', $this->log_target, $this->log_level);
            // check imagick
            if (!extension_loaded('imagick')) {
                $this->logger->error('imagick not installed');
            }
            // wm
            if ($this->useWm) {
                $this->_wm = new Imagick();
                if (!$this->_wm->readImage($this->imgPath.$this->wmFilename)) {
                    $this->logger->error($this->imgPath.$this->wmFilename.' not found, watermark not use.');
                    $this->_wm->destroy();
                }
                $this->_wm->setImageOpacity(0.1);
            }
            
            $this->_sklads = $this->core->getUnlockedSkladPrefixes();
        }
        
        private function readConfig(array $config) {
            $properties = get_class_vars(static::class);
            foreach ($properties as $property => $value) {
                if (isset($config[$property])) {
                    $this->$property = $config[$property];
                }
            }
        }
        
        public function addError($string) {
            $this->_error = $string;
        }
        
        public function getError() {
            return $this->_error;
        }

        /**
         * Читает необработанные картинки или конкретную картинку из базы
         * @param int $id id кантинки, default null
         * @return boolean
         */
        private function readItems($id = null) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\ItemImages');
            $where = array(
                'binary:IS NOT' => null,
                'prefix:IN' => $this->_sklads,
            );
            if (!empty($id)) {
                $where['id'] = $id;
            } else {
                $c->limit($this->limit);                
            }
            $c->where($where);
            if ($items = $this->core->xpdo->getCollection('Brevis\Model\ItemImages', $c)) {
                return $items;
            } else {
                return false;
            }
        }
        
        /**
         * Записывает обработанную картинку в базу
         * @param Brevis\Model\ItemImages $item
         */
        private function writeItem($item) {
            $item->set('filename', $item->id.'.jpg');
            $item->set('binary', null);
            $item->set('hash', $this->_hash);
            $item->save();
            $this->count['write']++;
        }
        
        /**
         * Обрабатывает картинку, генерит превьюшки
         * 
         * @param Brevis\Model\ItemImages $item
         * @return boolean
         */
        public function runItem($item) {
                
            $im = new Imagick();
            $im->readImageBlob(base64_decode($item->binary));
            $im->setImageFormat("jpeg");

            try {
                $im->thumbnailImage($this->imgSize['width'] , $this->imgSize['height'] , TRUE);
            }
            catch(Exception $e) {
               $this->logger->error('Caught exception: ',  $e->getMessage());
            }
            
            $path = $this->imgPath.'/'.$item->prefix;
            $this->checkDir($path);

            $fileName = $item->id.'.jpg';
            
            // fullsize
            $this->_writeFullSize($im, $path, $fileName);
            $this->_setHash($path.'/'.$fileName);

            // thumbs
            foreach ($this->imgThumbs as $thPath => $size) {
                $thPath = $path.'/'.$thPath;
                $this->checkDir($thPath);
                $imThumb = $im;
                try {
                    $imThumb->scaleImage($size[0], $size[1], true);
                }
                catch(Exception $e) {
                   $this->logger->error('Caught exception: ',  $e->getMessage());
                }
                $im->writeImage($thPath.$fileName);
                $imThumb->destroy();
            }

            $im->destroy();
            
            $this->count['run']++;
            return true;
        }
        
        /**
         * Запуск процесса
         */
        public function run($id = null) {
            if ($items = $this->readItems($id)) { 
                $this->count['read'] = count($items);
                foreach ($items as $item) {
                    if ($this->runItem($item)) {
                        $this->writeItem($item);
                    } else {
                        $this->logger->error('Не могу сделать превьюшки для ' . print_r($item->id, true));
                    }
                }
            }
            $this->logger->info(print_r($this->count, true));
            $this->logger->info(print_r($this->core->devStat(), true));
        }
        
        /**
         * Запуск процесса хеширования
         */
        public function generateHash($where) {
            $c = $this->core->xpdo->newQuery('Brevis\Model\ItemImages');
            $where = array_merge($where, [
                'filename:IS' => null,
                'prefix:IN' => $this->_sklads,
            ]);
            if ($items = $this->core->xpdo->getCollection('Brevis\Model\ItemImages', $c)) {
                foreach ($items as &$item) {
                    $path = $this->imgPath.$item->prefix;
                    $fileName = $item->filename;
                    $this->_setHash($path.'/'.$fileName);
                    $item->set('hash', $this->_hash);
                    $item->save();
                }
            }
        }
        
        /**
         * Проверка существования директории, создание при необходимости
         * @param string $path
         */
        private function checkDir($path) {
            if (!is_dir($path)) {
                try {
                    mkdir($path);
                }
                catch(Exception $e) {
                   $this->logger->error('Caught exception: ',  $e->getMessage());
                }
            }
        }
        
        /**
         * Записывает fullsize изображение
         * @param Imagick $im
         * @param string $path
         * @param string $filename
         * @return bool
         */
        private function _writeFullSize(Imagick $im, $path, $filename) {
            $success = false;
            if (!empty($this->_wm)) {
                $imWm = $im->textureimage($this->_wm);
                $success = $imWm->writeImage($path.'/'.$filename);
                $imWm->destroy();
            } else {
                $success = $im->writeImage($path.'/'.$filename);
            }
            return $success;
        }
        
        /**
         * Записывает thumbs
         * @param Imagick $im
         * @param string $path
         * @param string $filename
         */
        private function _writeThumbs(Imagick $im, $path, $filename) {
            foreach ($this->imgThumbs as $thPath => $size) {
                $thPath = $path.'/'.$thPath;
                $this->checkDir($thPath);
                $imThumb = $im;
                try {
                    $imThumb->scaleImage($size[0], $size[1], true);
                }
                catch(Exception $e) {
                   $this->logger->error('Caught exception: ',  $e->getMessage());
                }
                $imThumb->writeImage($thPath.$filename);
                $imThumb->destroy();
            }
        }
        
        /**
         * Генерирует превьюшки и сохраняет загруженный файл
         * @param string $file
         * @param string $prefix Префикс склада
         * @return filename or false
         */
        public function processUploadedFile($file, $prefix) {
            $im = new Imagick();
            if ($im->readimage($file)) {
                if (!$this->_imageExist($im->getimagesignature())) {
                    $im->setImageFormat("jpeg");
                    $geometry = $im->getimagegeometry();
                    if ($geometry['width'] > $this->imgSize['width'] or $geometry['height'] > $this->imgSize['height']) {
                        try {
                            $im->thumbnailImage($this->imgSize['width'] , $this->imgSize['height'] , TRUE);
                        }
                        catch(Exception $e) {
                           $this->logger->error('Caught exception: ',  $e->getMessage());
                        }
                    }
                    $path = $this->imgPath.'/'.$prefix;
                    $this->checkDir($path);
                    $fileName = uniqid().'.jpg';
                    // fullsize
                    if ($this->_writeFullSize($im, $path, $fileName)) {
                        $this->_writeThumbs($im, $path, $fileName);
                        $this->_setHash($path.'/'.$fileName);
                        if (!$this->_imageExist($this->_hash)) {
                            $this->_success = true;
                        } else {
                            $this->addError('Обнаружен дубликат. Такая фотография уже есть в базе.');
                        }
                    } else {
                        $this->addError('Не удалось записать fillsize.');
                    }
                } else {
                    $this->addError('Обнаружен дубликат. Такая фотография уже есть в базе.');
                }
            } else {
                $this->addError('Не удалось прочитать '.$file);
            }
            $im->destroy();
            unlink($file);
            return $this->_success ? $fileName : false;
        }
        
        /**
         * 
         * @return string image hash
         */
        public function getHash() {
            return $this->_hash;
        }
        
        private function _setHash($file) {
            $im = new Imagick($file);
            $this->_hash = $im->getimagesignature();
            $im->destroy();
        }


        /**
         * Проверяем существование файла по хешу
         * @param string $hash
         * @return bool
         */
        private function _imageExist($hash) {
            $count = $this->core->xpdo->getCount('Brevis\Model\ItemImages', ['hash' => $hash]);
            return !empty($count);
        }
    
    }