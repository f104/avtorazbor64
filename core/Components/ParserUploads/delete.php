<?php
    
    /**
     * Скрипт для крона. Парсинг складов.
     * Удаленные товары.
     */

    namespace Brevis\Components\ParserUploads;
    
    define('PROJECT_API_MODE', true);

    $base = dirname(dirname(dirname(dirname(__FILE__)))) . '/';
    require_once $base . 'index.php';
    
    // logger
    $logger = $Core->newLogger('ParserDeleted', PROJECT_LOG_TARGET, PROJECT_LOG_LEVEL);
    
    // получаем все незалоченные склады
    $sklads = $Core->xpdo->getCollection('\Brevis\Model\Sklad', ['locked' => 0]);
    foreach ($sklads as $sklad) {
        // проверяем наличие файла
        $filename = PROJECT_ASSETS_PATH . 'data/deleted_' . $sklad->prefix . '.xml';
        if (file_exists($filename)) {
            // лочим
            $Core->lockSklad($sklad->prefix);
            // загружаем файл
            $xml = simplexml_load_file($filename);
            if ($xml !== false) {
                $rows = [];
                foreach ($xml->tovar as $item) {
                    $rows[] = (string)$item['kode'];
                }
//                var_dump($rows);
                if (!empty($rows)) {
                    // картинки нужно удалять по одной, иначе не удаляются файлы
                    // нельзя писать условие выборки напрямую в getCollection!!!
                    $c = $Core->xpdo->newQuery('Brevis\Model\ItemImages', [
                        'prefix' => $sklad->prefix,
                        'item_key:IN' => $rows,
                    ]);
                    $images = $Core->xpdo->getCollection('Brevis\Model\ItemImages', $c);
                    foreach ($images as $image) {
                        $image->remove();
                    }
                    $items = $Core->xpdo->removeCollection('Brevis\Model\Item', [
                        'prefix' => $sklad->prefix,
                        'code:IN' => $rows,
                    ]);
                    $sklad->updateTime();
                    $logger->info('[' . $sklad->prefix . '] Удалено item/images: ' . $items . '/' . count($images));
                }
            } else {
                $logger->error('[' . $sklad->prefix . '] Не удалось загрузить xml из файла ' . $filename);
            }
            // удаляем файл после обработки
            @unlink($filename);
            // анлочим
            $Core->unlockSklad($sklad->prefix);
        }
    }

    