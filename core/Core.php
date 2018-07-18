<?php

    namespace Brevis;

    use \Fenom as Fenom;
    use \xPDO\xPDO as xPDO;
    use \Exception as Exception;
    use \Parsedown as Parsedown;
    use \Monolog\Logger;
    use \Monolog\Handler\StreamHandler;
    use \Monolog\Handler\EchoHandler;
    use Brevis\Components\MonologPHPMailerHandler\MonologPHPMailerHandler as MonologPHPMailerHandler;
    use Brevis\Components\FenomInlineProvider\FenomInlineProvider as FenomInlineProvider;

    class Core {

        public $config = array();

        /** @var Fenom $fenom */
        public $fenom;

        /** @var xPDO $xpdo */
        public $xpdo;

        /** @var Parsedown $parser */
        public $parser;

        /** @var User $user */
        public $authUser = null, $isAuth = false;

        /** @var Monolog $logger */
        public $logger;
        public $siteUrl, $baseUrl, $siteDomain, $useMunee, $useStat = true;
        
        /** @var string $citiKey City name */
        public $cityKey = null;
        
        /** @var xPDOCacheManager */
        public $cacheManager= null;
        
        /** @var int Время кеширования данных */
        public $cacheLifetime = 1800;    

        /**
         * Конструктор класса
         *
         * @param string $config Имя файла с конфигом
         */
        function __construct($config = 'config') {
            mb_internal_encoding("UTF-8");
            date_default_timezone_set('Europe/Samara');
            if (is_string($config)) {
                $config = dirname(__FILE__) . "/Config/{$config}.inc.php";
                if (file_exists($config)) {
                    require $config;
                    /** @var string $database_dsn */
                    /** @var string $database_user */
                    /** @var string $database_password */
                    /** @var array $database_options */
                    try {
                        $this->xpdo = new xPDO($database_dsn, $database_user, $database_password, $database_options);
                        $this->xpdo->setPackage('Model', PROJECT_CORE_PATH);
                        $this->xpdo->startTime = microtime(true);
                    } catch (Exception $e) {
                        exit($e->getMessage());
                    }
                } else {
                    exit('Не могу загрузить файл конфигурации');
                }
            } else {
                exit('Неправильное имя файла конфигурации');
            }
            
            $this->cacheManager = $this->xpdo->getCacheManager();

            $this->siteUrl = PROJECT_SITE_URL;
            // last /
            if ($this->siteUrl[strlen($this->siteUrl)-1] == '/') {
                $this->siteUrl = substr($this->siteUrl, 0, -1);
            }
            $this->baseUrl = PROJECT_SITE_URL.PROJECT_BASE_URL;
            $this->siteDomain = substr(PROJECT_SITE_URL, strpos(PROJECT_SITE_URL, '://') + 3);
            $this->useMunee = PROJECT_USE_MUNEE;
            $this->useStat = PROJECT_USE_STAT;

            $this->xpdo->setLogLevel(defined('PROJECT_LOG_LEVEL') ? PROJECT_LOG_LEVEL : xPDO::LOG_LEVEL_ERROR);
            $this->xpdo->setLogTarget(defined('PROJECT_LOG_TARGET') ? PROJECT_LOG_TARGET : 'FILE');

            // Create core logger
            $this->logger = $this->newLogger('core', PROJECT_LOG_TARGET, PROJECT_LOG_LEVEL);

            // check auth
            $processor = $this->runProcessor('Security\Check');
        }

        /**
         * Create new Monolog\Logger
         * @param string $name
         * @param string $target ECHO || FILE || MAIL
         * @param string $level Monolog level
         * @return Logger
         */
        public function newLogger($name, $target, $level) {
            $logger = new Logger($name);
            switch ($target) {
                case 'MAIL':
                    $logger->pushHandler(new MonologPHPMailerHandler(PROJECT_MAIL_ADMIN, $level));
                    break;
                case 'FILE':
                    $logger->pushHandler(new StreamHandler(PROJECT_LOG_PATH . "/$name.log", $level));
                    break;
                default: // 'ECHO'
                    $logger->pushHandler(new EchoHandler($level));
                    break;
            }
            return $logger;
        }

        /**
         * Обработка входящего запроса
         *
         * @param $uri
         */
        public function handleRequest($uri) {
            // check last slash
            if (strrpos($uri, '/') == strlen($uri) - 1) {
                $uri = substr($uri, 0, -1);
            }
            $request = explode('/', $uri);

            $className = '\Brevis\Controllers\\' . ucfirst(array_shift($request));
            /** @var Controller $controller */
            if (!class_exists($className)) {
                if (empty($uri)) {
                    // index page
                    $controller = new Controllers\Cars($this);
                } else {
                    $this->sendErrorPage();
                }
            } else {
                $controller = new $className($this);
            }
            $controller->isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
            $initialize = $controller->initialize($request);
            if ($initialize === true) {
//                $controller->isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
                $response = $controller->run();
            } elseif (is_string($initialize)) {
                $response = $initialize;
            } else {
                $response = 'Возникла неведомая ошибка при загрузке страницы';
            }

            if ($controller->isAjax) {
                $this->ajaxResponse(false, 'Не могу обработать ajax запрос');
            } else {
                echo $response;
            }
        }

        /**
         * Запуск процессора
         *
         * @param string $name
         * @param array $options
         * @return Processor
         */
        public function runProcessor($name, $options = array()) {

            $className = '\Brevis\Processors\\' . $name;
            /** @var Processor $processor */
            try {
                $processor = new $className($this);
                $processor->initialize($options);
                $processor->run();
                return $processor;
            } catch (Exception $e) {
                $this->core->log($e->getMessage());
            }
//            if (!class_exists($className)) {
//                $response = 'Нет такого класса' . $className;
//            } else {
//                $processor = new $className($this);
//                $processor->initialize($options);
//                $response = $processor->run();
//                return $processor;
//            }
//            return $response;
        }

        /**
         * Получение экземпляра класса Fenom
         *
         * @return bool|Fenom
         */
        public function getFenom() {
            if (!$this->fenom) {
                try {
                    if (!file_exists(PROJECT_CACHE_PATH)) {
                        mkdir(PROJECT_CACHE_PATH);
                    }
                    $this->fenom = Fenom::factory(PROJECT_TEMPLATES_PATH, PROJECT_CACHE_PATH, PROJECT_FENOM_OPTIONS);
                    $provider = new FenomInlineProvider();
                    $this->fenom->addProvider("inline", $provider);
                    $modifiers = $this->_fenomCustomModifiers();
                    foreach ($modifiers as $name => $func) {
                        $this->fenom->addModifier($name, $func);
                    }
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                    return false;
                }
            }

            return $this->fenom;
        }

        private function _fenomCustomModifiers() {

            $modifiers = [
                'json_encode' => 'json_encode',
                'json_decode' => 'json_decode',
            ];

            $modifiers['declension'] = $modifiers['decl'] = function ($amount, $variants, $number = false, $delimiter = ',') {
                $variants = explode($delimiter, $variants);
                if (count($variants) < 2) {
                    $variants = array_fill(0, 3, $variants[0]);
                } elseif (count($variants) < 3) {
                    $variants[2] = $variants[1];
                }
                $modulusOneHundred = $amount % 100;
                switch ($amount % 10) {
                    case 1:
                        $text = $modulusOneHundred == 11 ? $variants[2] : $variants[0];
                        break;
                    case 2:
                    case 3:
                    case 4:
                        $text = ($modulusOneHundred > 10) && ($modulusOneHundred < 20) ? $variants[2] : $variants[1];
                        break;
                    default:
                        $text = $variants[2];
                }
                return $number ? $amount . ' ' . $text : $text;
            };
            
            $modifiers['dump'] = function ($var) {
                return print_r($var, true);
            };
            
            return $modifiers;
        }

        /**
         * Получение парсера текстов
         *
         * @return Parsedown
         */
        public function getParser() {
            if (!$this->parser) {
                $this->parser = new Parsedown();
            }

            return $this->parser;
        }

        /**
         * Метод удаления директории с кэшем
         *
         */
        public function clearCache() {
            Core::rmDir(PROJECT_CACHE_PATH);
            mkdir(PROJECT_CACHE_PATH);
        }

        /**
         * Логирование. Пока просто выводит ошибку на экран.
         *
         * @param $message
         * @param $level
         */
        public function log($message, $level = E_USER_ERROR) {
            if (!is_scalar($message)) {
                $message = print_r($message, true);
            }
            trigger_error($message, $level);
        }

        /**
         * Удаление ненужных файлов в пакетах, установленных через Composer
         *
         * @param mixed $base
         */
        public static function cleanPackages($base = '') {
            if (!is_string($base)) {
                $base = dirname(dirname(__FILE__)) . '/vendor/';
            }
            if ($dirs = @scandir($base)) {
                foreach ($dirs as $dir) {
                    if (in_array($dir, array('.', '..'))) {
                        continue;
                    }
                    $path = $base . $dir;
                    if (is_dir($path)) {
                        if (in_array($dir, array('tests', 'test', 'docs', 'gui', 'sandbox', 'examples', '.git'))) {
                            Core::rmDir($path);
                        } else {
                            Core::cleanPackages($path . '/');
                        }
                    } elseif (pathinfo($path, PATHINFO_EXTENSION) != 'php') {
                        unlink($path);
                    }
                }
            }
        }

        /**
         * Рекурсивное удаление директорий
         *
         * @param $dir
         */
        public static function rmDir($dir) {
            $dir = rtrim($dir, '/');
            if (is_dir($dir)) {
                $objects = scandir($dir);
                foreach ($objects as $object) {
                    if ($object != '.' && $object != '..') {
                        if (is_dir($dir . '/' . $object)) {
                            Core::rmDir($dir . '/' . $object);
                        } else {
                            unlink($dir . '/' . $object);
                        }
                    }
                }
                rmdir($dir);
            }
        }

        /**
         * Вывод ответа в установленном формате для всех Ajax запросов
         *
         * @param bool|true $success
         * @param string $message
         * @param array $data
         */
        public function ajaxResponse($success = true, $message = '', array $data = array()) {
            $response = array(
                'success' => $success,
                'message' => $message,
                'data' => $data,
            );

            exit(json_encode($response));
        }

        /**
         * Выводит статистику память/время
         */
        public function logDevStat() {
            $memory = memory_get_usage();
            $time = microtime(true) - $this->xpdo->startTime;
            $this->logger->info("Memory: $memory\r\nTime: $time");
        }

        public function devStat() {
            $memory = memory_get_usage();
            $time = microtime(true) - $this->xpdo->startTime;
            return "Memory: $memory\r\nTime: $time";
        }

        /**
         * Лочит склад
         * @param string $prefix
         * @return boolean
         */
        public function lockSklad($prefix) {
            if ($sklad = $this->xpdo->getObject('Brevis\Model\Sklad', array('prefix' => $prefix))) {
                $sklad->set('locked', 1);
                $sklad->save();
                return true;
            } else {
                $this->logger->error('Can\'t lock sklad with prefix ' . $prefix);
                return false;
            }
        }

        /**
         * Анлочит склад
         * @param string $prefix
         * @return boolean
         */
        public function unlockSklad($prefix) {
            if ($sklad = $this->xpdo->getObject('Brevis\Model\Sklad', array('prefix' => $prefix))) {
                $sklad->set('locked', 0);
                $sklad->save();
                return true;
            } else {
                $this->logger->error('Can\'t unlock sklad with prefix ' . $prefix);
                return false;
            }
        }

        /**
         * Выборка складов для отображения.
         */
        public function getSklads() {
            $c = $this->xpdo->newQuery('Brevis\Model\Sklad');
            $c->select($this->xpdo->getSelectColumns('Brevis\Model\Sklad', 'Sklad', '', ['id']));
            $c->innerJoin('Brevis\Model\SkladStatus', 'Status');
            $c->innerJoin('Brevis\Model\Supplier', 'Supplier');
            $c->leftJoin('Brevis\Model\SupplierStatus', 'SupplierStatus', ['SupplierStatus.id = Supplier.status_id']);
            $c->where(array(
//                'locked' => 0, // не заблокирован
                'switchon' => 1, // включен
                'Status.show' => 1, // статус склада участвует в выдаче
                'SupplierStatus.show' => 1, // статус поставщика участвует в выдаче
            ));
            //        $c->prepare(); die($c->toSQL());
            if ($c->prepare() && $c->stmt->execute()) {
                return $rows = $c->stmt->fetchAll(\PDO::FETCH_COLUMN);
            } else {
                $this->log('Не могу выбрать Sklad:' . print_r($c->stmt->errorInfo(), true));
            }
        }

        /**
         * Возвращает массив "незалоченных" складов
         * ['prefix1', 'prefix2', ...]
         * 
         * @return array
         */
        public function getUnlockedSkladPrefixes() {
            $res = array();
            $c = $this->xpdo->newQuery('Brevis\Model\Sklad');
            $c->where(array(
                'locked' => 0,
            ));
            $c->select('prefix');
            if ($c->prepare() && $c->stmt->execute()) {
                $rows = $c->stmt->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $item) {
                    $res[] = $item['prefix'];
                }
            }
            return $res;
        }

        public function sendErrorPage($code = 404) {
            $errorController = new Controllers\Error($this);
            switch ($code) {
                case 403:
                    header('HTTP/1.0 403 Forbidden');
                    $className = '\Brevis\Controllers\Forbidden';
                    break;
                default:
                    header("HTTP/1.0 404 Not Found");
                    $className = '\Brevis\Controllers\NotFound';
                    break;
            }
            $controller = new $className($this);
            $controller->initialize();
            echo $controller->run();
            @session_write_close();
            exit();
        }

        /**
         * Чистит пользовательский ввод
         * @param array or string $val
         * @return array or string
         */
        public function cleanInput($val) {
            if (is_array($val)) {
                foreach ($val as &$v) {
                    $v = trim(strip_tags($v));
                }
            } else {
                $val = trim(strip_tags($val));
            }
            return $val;
        }

        /**
         * Выборка пользователей id=>email
         * @var int group Ограничевающая группа
         * @return array id=>email (name)
         */
        public function getUsersList($group = 0) {
            $c = $this->xpdo->newQuery('Brevis\Model\User');
//           $c->select($this->xpdo->getSelectColumns('Brevis\Model\User', 'User', '', ['id', 'CONCAT(email,name)']));
            $c->select("User.id, CONCAT_WS(' ', User.email, User.name)");
            $c->sortby('name', 'ASC');
            if (!empty($group)) {
                $c->leftJoin('Brevis\Model\UserGroupMember', 'UserGroupMembers');
                $c->where(['UserGroupMembers.group_id' => $group]);
            }
//           $c->prepare(); die($c->toSQL());
            if ($c->prepare() && $c->stmt->execute()) {
                return $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
            } else {
                $this->log('Не могу выбрать User:' . print_r($c->stmt->errorInfo(), true));
            }
        }

        /**
         * Формула стоимости детали
         * @param array $item Должна приходить с region_id и increase_category элемента
         * @param Brevis\Model|User $user покупатель
         * @return integer
         */
        public function calculatePrice(&$item, $user) {
            $moscowID = 11;
            
            // формируем анонима
            if (empty($user)) {
                $user = $this->xpdo->newObject('Brevis\Model\User', [
                    'buyer_level' => 1,
                    'region_id' => $moscowID,
                ]);
            }
            
            // уровни цен для покупателей
            if (!isset($this->buyerlevels)) {
                $c = $this->xpdo->newQuery('Brevis\Model\BuyerLevel');
                $c->select('id, increase');
                if ($c->prepare() and $c->stmt->execute()) {
                    $this->buyerlevels = $c->stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
                }
            }
            
            // маржа Русанова
            $kRusanov = 1 + $this->buyerlevels[$user->buyer_level] / 100;
            $item['price'] = $item['price'] * $kRusanov;
            $item['delivery'] = 'В течение дня';
            
            return $item['price'];
        }
        
        /**
         * Сумма платежа с учетом комиссии агрегатора
         * @param float $payment Сумма платежа
         * @return float
         */
        public function calculatePayment($payment) {
            return defined('PROJECT_MERCHANT_PERCENT') ? $payment * (1 + PROJECT_MERCHANT_PERCENT / 100) : $payment;
        }
    }
    