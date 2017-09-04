<?php

namespace Brevis\Controllers;
use PHPExcel as PHPExcel;
use PHPExcel_IOFactory as PHPExcel_IOFactory;
use PHPExcel_CachedObjectStorageFactory as PHPExcel_CachedObjectStorageFactory;
use PHPExcel_Settings as PHPExcel_Settings;

/**
 * Export to excel
 */
class Export extends \Brevis\Controller {
    
    /** @var array список колонок для экспорта */
    public $columns;
    /** @var string префикс для лексикона */    
    public $langPrefix;
    /** @var string имя экспортируемого файла */    
    public $filename;
    
    private $_objPHPExcel;
    
    function __construct(\Brevis\Core $core) {
        parent::__construct($core);
        if (empty($_REQUEST['classname']) or empty($_REQUEST['filters'])) {
            $this->core->sendErrorPage();
        }
        if (!isset($_REQUEST['columns']) or empty($_REQUEST['columns'])) {
            die('Укажите хотя бы один стобец данных.');
        }
        foreach (['classname', 'filters', 'columns'] as $item) {
            $this->$item = $_REQUEST[$item];
        }
        $this->filters = json_decode($this->filters, true);
    }
    
    public function run() {
        // подключаем класс
        $controller = new $this->classname($this->core);
        $controller->_readFilters($this->filters);
        // получаем данные
        $rows = $controller->getRows(true);
        // подключаем лексиконы
        foreach ($controller->langTopic as $topic) {
            $this->lang = array_merge($this->lang, $this->loadLexicon($topic));
        }
        // устанавливаем префикс для лексикона
        $this->langPrefix = $controller->getDataClassName(true, true);        
        // и имя файла для загрузки
        $this->filename = $this->lang[str_replace('`', '', $this->core->xpdo->getTableName($controller->classname))];

        // проверим на выборку несанкционированных данных
        foreach ($this->columns as $k => $item) {
            if (!in_array($item, $controller->exportColumns)) {
                unset($this->columns[$k]);
            }
        }
        
        // готовим и отдаем файл
        $this->_prepareXls();
        $this->_writeXls($rows);
        $this->_outputXls();
        
    }
    
    /**
     * Подготавливает объект PHPExcel
     */
    private function _prepareXls() {
        $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_phpTemp;
        $cacheSettings = array( ' memoryCacheSize ' => '8MB');
        PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
        $this->_objPHPExcel = new PHPExcel();
        $this->_objPHPExcel->getProperties()->setCreator('Авторазбор Авангард');
        $this->_objPHPExcel->setActiveSheetIndex(0);
        $this->_objPHPExcel->getActiveSheet()->setTitle($this->filename);
    }
    
    /**
     * Пишет данные в объект PHPExcel
     * @param array $rows
     */
    private function _writeXls($rows) {
        // стиль первой строки
        $styleFirstRow = [
            'font' => [
                'bold' => true,
            ]
        ];
        $sheet = $this->_objPHPExcel->getActiveSheet();
        // первая строка
        foreach ($this->columns as $k => $v) {
            $sheet->setCellValueByColumnAndRow($k, 1, $this->lang[$this->langPrefix.'.'.$v]);
            $sheet->getCellByColumnAndRow($k, 1)->getStyle()->applyFromArray($styleFirstRow);
	}
        // данные
        foreach ($rows as $k => $v) {
            for ($i = 0; $i < count($this->columns); $i++) {
                $sheet->setCellValueByColumnAndRow($i , $k + 2, $v[$this->columns[$i]]);
            }
        }
        // подгоняем размер
        $toCol = $sheet->getColumnDimension($sheet->getHighestColumn())->getColumnIndex();
        $toCol++;
        for($i = 'A'; $i != $toCol; $i++) {
            $sheet->getColumnDimension($i)->setAutoSize(true);
        }
    }

    /**
     * Отдаем объект PHPExcel в вывод и прерывает работу
     */
    private function _outputXls() {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$this->filename.'.xls"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0
        $objWriter = PHPExcel_IOFactory::createWriter($this->_objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
}
