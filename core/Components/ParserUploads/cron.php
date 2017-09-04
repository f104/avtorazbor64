<?php
    
    /**
     * Скрипт для крона. Парсинг складов
     * @deprecated 
     */

    namespace Brevis\Components\ParserUploads;
    
    use Brevis\Components\ParserUploads\ParserUploads as ParserUploads;
    use Brevis\Components\Checker\Checker as Checker;

    define('PROJECT_API_MODE', true);

    $base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
    require_once $base . 'index.php';
    
    // получаем все незалоченные склады
    $sklads = $Core->xpdo->getCollection('\Brevis\Model\Sklad', ['locked' => 0]);
    foreach ($sklads as $sklad) {
        // проверяем наличие файла
        $filename = PROJECT_ASSETS_PATH . 'data/' . $sklad->prefix . '.xml';
        if (file_exists($filename)) {
            // проверяем дату
            if (strtotime($sklad->updatedon) < filemtime($filename)) {
                // лочим
                $Core->lockSklad($sklad->prefix);
                

                // парсим
                
                // инициализация ридера
                $reader = new ParserUploads($Core, $sklad, $filename, [
                    'log_level' => 'INFO',
                    'log_target' => 'FILE',
                ]);

                // эта анонимная функция будет вызвана сразу по завершению парсинга элементов <Prefix>
                $reader->onEvent('parsePrefiks', function($context) {
                    $prefix = $context->sklad->prefix;
                    // удаляем из БД все записи с нашим префиксом
                    $table = $context->xPDO->getTableName('Brevis\Model\Item');
                    $context->xPDO->exec("DELETE FROM $table WHERE `prefix` = '$prefix'");
                    $table = $context->xPDO->getTableName('Brevis\Model\ItemImages');
                    $context->xPDO->exec("DELETE FROM $table WHERE `prefix` = '$prefix'");
                    $context->prepareImgDir();
                });

                // эта анонимная функция будет вызвана сразу по завершению парсинга элементов <tovar>
                $reader->onEvent('parsetovar', function($context) {
                    $tovar = $context->getResult()['tovar'][0];
                    // сохраняем её в БД
                    $tovarModel = $context->xPDO->newObject('Brevis\Model\Item');;
                    $tovarModel->fromArray($tovar);
                    $tovarModel->save();
                    unset($tovar, $tovarModel);
                    $context->id = $context->xPDO->lastInsertId();
                    $context->countItem++;
                });

                $reader->onEvent('parsepicture', function($context) {
                    $result = $context->getResult();
                    // сохраняем в БД
                    $imageModel = $context->xPDO->newObject('Brevis\Model\ItemImages');;
                    $imageModel->set('item_key', $result['tovar'][0]['code']);
                    $imageModel->set('item_id', $context->id);
                    $imageModel->set('prefix', $result['tovar'][0]['prefix']);
                    $imageModel->set('binary', $result['pictures'][0]);
                    $imageModel->save();
                    unset($result, $imageModel);
                    $context->clearPictures();
                    $context->countImg++;
                });

                $reader->onEvent('beforeParseContainer', function($name, $context) {
                    if ($name == 'Prefiks' or $name == 'tovar') { 
                        // чистим память
                        $context->clearResult(); 
                    }
                });

                // запускаем парсинг
                $reader->parse();
                $reader->logger->info("Parse ".$reader->sklad->prefix."<br>Items $reader->countItem<br>ItemImages $reader->countImg");
                $reader->logger->info(memory_get_usage());
                $time = microtime(true) - $reader->xPDO->startTime;
                $reader->logger->info($time);
                
                
                
                
                
                // обновляем дату
                $sklad->set('updatedon', date('Y-m-d H:i:s',  time()));
                $sklad->save();
                // проверка
                $checker = new Checker($Core, [
                    'sklad_id' => $sklad->id,                    
                ]);
                $checker->run();
                // анлочим
                $Core->unlockSklad($sklad->prefix);
                // выходим, делаем по одному складу
                break;
            }
        }
    }

    