<?php
    
    /**
     * Скрипт для крона. Парсинг складов.
     * Новые/измененные товары.
     */

    namespace Brevis\Components\ParserUploads;
    
    use Brevis\Components\ParserUploads\ParserUploads as ParserUploads;
    use Brevis\Components\Checker\Checker as Checker;

    define('PROJECT_API_MODE', true);
    ini_set('max_execution_time', 0);
    set_time_limit(0);

    $base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
    require_once $base . 'index.php';
    
    // получаем все незалоченные склады
    $sklads = $Core->xpdo->getCollection('\Brevis\Model\Sklad', ['locked' => 0]);
    foreach ($sklads as $sklad) {
        // проверяем наличие файла
        $filename = PROJECT_ASSETS_PATH . 'data/changed_' . $sklad->prefix . '.xml';
        if (file_exists($filename)) {
            // лочим
            $Core->lockSklad($sklad->prefix);

            // инициализация ридера
            $reader = new ParserUploads($Core, $sklad, $filename, [
                'log_level' => 'INFO',
                'log_target' => 'FILE',
            ]);

            // эта анонимная функция будет вызвана сразу по завершению парсинга элементов <sklad>
            $reader->onEvent('parsesklad', function($context) {
                $context->prepareImgDir(false);
            });

            // эта анонимная функция будет вызвана сразу по завершению парсинга элементов <tovar>
            $reader->onEvent('parsetovar', function($context) {
                $tovar = $context->getResult()['tovar'][0];
                // сохраняем её в БД
                if (!$tovarModel = $context->xPDO->getObject('Brevis\Model\Item', [
                    'prefix' => $tovar['prefix'],
                    'code' => $tovar['code'],
                ])) {
                    $tovarModel = $context->xPDO->newObject('Brevis\Model\Item');
                }
                $tovarModel->fromArray($tovar);
                $tovarModel->save();
                $context->id = $tovarModel->id;
                $context->countItem++;
                // удаляем старые картинки
                $images = $context->xPDO->getCollection('Brevis\Model\ItemImages', [
                    'prefix' => $tovar['prefix'],
                    'item_key' => $tovar['code'],
                ]);
                foreach ($images as $image) {
                    $image->remove();
                }
                unset($tovar, $tovarModel, $images, $image);
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
            $sklad->updateTime();
            // проверка
            $checker = new Checker($Core, [
                'sklad_id' => $sklad->id,                
            ]);
            $checker->run();
            // удаляем файл после обработки
            @unlink($filename);
            // анлочим
            $Core->unlockSklad($sklad->prefix);
            // выходим, делаем по одному складу
            break;
        }
    }

    