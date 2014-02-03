<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 27.12.13
 */

namespace PriceParse\FileModel;


use PriceParse\CronModel\CronModel;
use PriceParse\Model\TableModel;

class CronAllFileModel {
    public static $allSwitcher;//флаг для парсинга по всем конкурентам
    private $_fileNameAllFromFtp;//имя нужного файла найденного на фтп копуса который надо скачать
    private $_adminModel;

    CONST FTP_325_ADDRESS_ALL_PRICE_ITEMS="ftp://80.93.48.179/prices/";
    public function __construct($adminModel){
        //записываем в логу о начале скачки файла по всем конкурентам с нашего фтп,проверка на размер лог-файла.
        CronModel::CronLogger('--------------------крон запущен на скачку файла всех конкурентов с фтп---------------------------','all','check');
        CronModel::deleteOldLoadedFileForCron(array(CronModel::CRON_LOAD_TXT_FILE));//на всяк случай удаляем если вдруг есть
        $this->_adminModel=$adminModel;
        $downloadResult=$this->downloadAllPriceItemsFromFtp();
        if($downloadResult){
            $this->createAllMaskInTableForCron($this->_adminModel);//вставляем слепок в БД по скаченному файлу с фтп
            CronModel::CronLogger('файл '.$this->_fileNameAllFromFtp.' успешно скопирован с фтп и по файлу сделан слепок в БД','all');
            //удаляем CronModel::CRON_LOAD_TXT_FILE_KOMUS, результат уже в базе
            CronModel::deleteOldLoadedFileForCron(array(CronModel::CRON_LOAD_TXT_FILE));
        }


    }
    /**
     * определяем имя TXT файла в директории FTP который надо скачать,скачиваем txt файл по ВСЕМ конрурентам с FTP 325
     *
     * @return bool
     */
    private function downloadAllPriceItemsFromFtp(){
        $pattern='/^All*?[^R]+$/';
        $this->_fileNameAllFromFtp=CronModel::getTodayFileNameFromFtp($pattern,self::FTP_325_ADDRESS_ALL_PRICE_ITEMS);
        if(isset($this->_fileNameAllFromFtp)){
            return CronModel::tryDownloadAndSaveFileFromFtp(self::FTP_325_ADDRESS_ALL_PRICE_ITEMS.$this->_fileNameAllFromFtp,CronModel::CRON_LOAD_TXT_FILE);
        }
        CronModel::CronLogger('ERROR не удалось скачать файл с '.self::FTP_325_ADDRESS_ALL_PRICE_ITEMS.' сменили название файла на ftp.','all');
        return false;
    }

    /**
     *подготавливаем таблицу для парсинга с комуса
     *
     * @param $adminModel
     */
    private function createAllMaskInTableForCron($adminModel){
        CronAllFileModel::$allSwitcher='on';
        $tableModel=new TableModel($adminModel,'create_crone_table');

    }

    /**
     * Проверка последней версии файла-парсинг-задания на фтп
     *
     * @param $adminModel   адаптер
     *
     * @param   string  $path   путь до лог-файла(lastModifyAll.log) в котором лежит timestamp последней модификации  файла-парсинг-задания на фтп
     *
     * @return bool
     */
    public static function checkFileAllLastModify($adminModel,$path){
        if(file_exists($path)){
            $stringFromfile=file_get_contents($path);//время создания файла по которому был произведен парсинг в прошлый раз,читаем из lastModifyKomus.log
            $pattern='/^All*?[^R]+$/';
            $remoteFile="prices/".CronModel::getTodayFileNameFromFtp($pattern, self::FTP_325_ADDRESS_ALL_PRICE_ITEMS);//имя файла с фтп

            $attempt=0;//три попытки на получение времяни создания файла-задания на ФТП(для обрыва соединения)
            do{
                $timeLastModify=CronModel::getTimeLastModifyFileOnFtp($remoteFile);
                $attempt++;
            }while($attempt<3 && $timeLastModify=='-1');

            if($timeLastModify=='-1'){
                CronModel::CronLogger('WARNING!!!Не удалось получить дату создания файла-задания с ФТП.','all');
                return false;
            }
            if($stringFromfile===$timeLastModify) return false;
            $cronModel= new CronModel($adminModel);
            $statusMessage=$cronModel->setStatusAnswer('all');//получаю статус таблицы в БД
            if($statusMessage==CronModel::TABLE_NAME_ALL_PARSE.' table is empty'){
                $changeLastModifyLog=function() use($path,$timeLastModify){
                    $file=fopen($path,'w');
                    fwrite($file,$timeLastModify);
                    fclose($file);
                };
                $changeLastModifyLog();
                return true;
            }
            return false;
        }
    }
} 