<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 19.12.13
 */
namespace PriceParse\CronModel;

use Application\Model\ConfigModel;
use PriceParse\FileModel\CronKomusFileModel;
use PriceParse\FileModel\FileModel;
use PriceParse\FileModel\KomusFileModel;
use PriceParse\LoggerModel\CronKomusMessageToLogFilePriceParse;
use PriceParse\LoggerModel\CronMessageToLogFilePriceParse;
use PriceParse\Model\TableModel;

class CronModel {
    private $_adminModel;

    public static $cronAllSwitcher;

    CONST TABLE_NAME_ALL_PARSE='parse_competitors_prices_cron';
    CONST TABLE_NAME_KOMUS_PARSE='parse_komus_diff_prices';


    CONST CRON_LOAD_TXT_FILE="./data/cronData/cron_price_data.txt";//имя для загруженного TXT файла с которого строится заготовка в табл. БД для ночного парсинга с крона по ВСЕМ конкурентам
    //CONST CRON_RESULT_TXT_FILE="./data/cronData/cron_price_result.txt";//путь и имя для загруженного TXT файла для парсинга с крона

    CONST CRON_LOAD_TXT_FILE_KOMUS="./data/cronData/cron_price_data_komus.txt";//имя для загруженного TXT файла с которого строится заготовка в табл. БД для ночного парсинга с крона по КОМУСУ,а так же будет называться файл который скачали с FTP и перевели в формат TXT
    //CONST CRON_RESULT_TXT_FILE_KOMUS="./data/cronData/cron_price_result_komus.txt";//путь и имя для загруженного TXT файла для парсинга с крона

    CONST USER_LOAD_SELECT_TXT="./data/cronData/user_load_select.txt";//имя для загруженного ЮЗЕРОМ TXT-запрос-файла для выборки  по результату с крона по ВСЕМ конкурентам
    CONST RESULT_SELECT_TXT_ON_USER="./data/cronData/result_select_on_user.txt";//имя файла с РЕЗУЛЬТАТОМ(выборка по USER_LOAD_SELECT_TXT) который будет ВЫДАН ЮЗЕРУ  по ссылке по ВСЕМ конкурентам
//todo может быть не надо так как неизвестно в каком виде выдавать
    CONST USER_LOAD_SELECT_TXT_KOMUS="./data/cronData/user_load_select_komus.txt";//имя для загруженного ЮЗЕРОМ TXT-запрос-файла для выборки  по результату с крона по КОМУСУ-FTP
    CONST RESULT_SELECT_TXT_ON_USER_KOMUS="./data/cronData/result_select_on_user_komus.txt";//имя файла с РЕЗУЛЬТАТОМ(выборка по USER_LOAD_SELECT_TXT_KOMUS) который будет ВЫДАН ЮЗЕРУ  по ссылке по КОМУСУ-FTP



    public function __construct($adminModel,$order='',$item=''){
        $this->_adminModel=$adminModel;
    }

    public  function setStatusAnswer($item){
       $tableNameForItem=$this->askTableNameForItem($item);
        $tableEmpty=$this->checkExistenceFileMaskOnTableInDbWhereStatusNot($tableNameForItem);
        if($tableEmpty) return $tableNameForItem." isset status not";//существует таблица и в ней существую не спарсенные строки
        $count=$this->checkCountInTable($tableNameForItem);//проверка на существование строк в таблице
        return(!$count)?$tableNameForItem." table is empty":$tableNameForItem." table is full";
        //return $tableNameForItem." table is empty";
    }

    private function askTableNameForItem($item){
        if($item=='komus'){
            return self::TABLE_NAME_KOMUS_PARSE;
        }
            return self::TABLE_NAME_ALL_PARSE;
    }



    public  function checkExistenceFileMaskOnTableInDbWhereStatusNot($table){
        $tableModel=new TableModel($this->_adminModel);
        if($table==self::TABLE_NAME_ALL_PARSE){
            $columns=array('product_1c_code','competitor_code','articul_product');
        }
        else{
            $columns=array('product_1c_code_3259404','articul_product_komus');
        }
        return $tableModel->getDataFromDBForPriceParseWithLimitWhereStatusOff($table,$columns);
    }

    public function checkCountInTable($table){
        $tableModel=new TableModel($this->_adminModel);
        return $tableModel->getCountTableFromDb($table);
    }

    public function letsParseThisItem($item){

        if($item=='komus'){
            $model=new KomusFileModel($this->_adminModel);
        }
        else{
            self::$cronAllSwitcher='on';
            $model=new FileModel($this->_adminModel);
        }
    }


    public function  chooseFutureCronMoon($statusMessage){
        if($statusMessage==self::TABLE_NAME_ALL_PARSE.' isset status not'){
            $this->letsParseThisItem('all');
        }
        if($statusMessage==self::TABLE_NAME_KOMUS_PARSE.' isset status not'){
            $this->letsParseThisItem('komus');
        }
        //если в таблице не осталось не спарсенных товаров
        if($statusMessage==self::TABLE_NAME_KOMUS_PARSE.' table is full'){
            CronModel::CronLogger('Парсинг товаров комуса успешно окончен','komus');
            //удаляем т.к CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS остался с прошлого раза(остается на всек случай,вдруг затык через отправку с курла),
            FileModel::garbageAllForCron(array(CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS));
            //создание нового CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS
            $param=new TableModel($this->_adminModel,'dump_table_komus','komus');
            //записываем результат на фтп
            $this->letsSendToFtpThisDataFromTable('komus');
            //очищаем таблицу
            $param->clearTableFromParseData(self::TABLE_NAME_KOMUS_PARSE);//очистили таблицу
            CronModel::CronLogger('--------------------Результат по комусу записан на фтп.---------------------------','komus');
            //todo переименовать  CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS в result_OLD
        }
        //todo придумать другой вариант без повтора логики
        //если в таблице не осталось не спарсенных товаров
        if($statusMessage==self::TABLE_NAME_ALL_PARSE.' table is full'){
            CronModel::CronLogger('Парсинг товаров по всем конкурентам успешно окончен','all');
            //удаляем т.к CronModel::RESULT_SELECT_TXT_ON_USER остался с прошлого раза(остается на всек случай,вдруг затык через отправку с курла),
            FileModel::garbageAllForCron(array(CronModel::RESULT_SELECT_TXT_ON_USER));
            //создание нового CronModel::RESULT_SELECT_TXT_ON_USER
            $param=new TableModel($this->_adminModel,'dump_table','cron-all');
            //записываем результат на фтп
            $this->letsSendToFtpThisDataFromTable('all');
            //очищаем таблицу
            $param->clearTableFromParseData(self::TABLE_NAME_ALL_PARSE);//очистили таблицу
            CronModel::CronLogger('--------------------Результат по всем конкурентам записан на фтп.---------------------------','all');
            //todo переименовать  CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS в result_OLD
        }

    }

    public function letsSendToFtpThisDataFromTable($item){
        //if($item=='komus' && file_exists(CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS)){
        if($item=='komus'){
            $localFile=CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS;
            $remoteFile='prices/Komus_Result.txt';
           // CronKomusFileModel::sendResultFileOnFtp($localFile,$remoteFile);
            //удаляю локальный  файл в который дампнули результат парсинга и файл в который слили данные с фтп,


        }
        else{
            $localFile=CronModel::RESULT_SELECT_TXT_ON_USER;
            $remoteFile='prices/All_Result.txt';
        }
        CronKomusFileModel::sendResultFileOnFtp($localFile,$remoteFile);

    }

    /**
     * запись сообщения в логу для крона
     *
     * @param   string  $message    сообщение в логу
     * @param   string  $item    флаг в какой файл писать komus|all
     * @param string $checkerSize   не пуста когда fileSize > 100 килоБайт
     */
    public static function CronLogger($message,$item,$checkerSize=''){
        $cronLogger=new CronMessageToLogFilePriceParse($item,$checkerSize);
        $cronLogger->sendMessage($message);
    }

    /**
     * получает имя файла с FTP который надо скачать
     * выбираем имя файла по паттерну
     *
     * @param   string  $pattern    паттерн поиска
     *
     * @param   string  $ftpAddress адрес до директории с файлом
     *
     * @return string   имя файла который надо скачать с фтп
     */
    public static function getTodayFileNameFromFtp($pattern,$ftpAddress){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,  $ftpAddress);
        curl_setopt($curl, CURLOPT_USERPWD, self::getStringForCurlUserPwd());
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1) ;
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'NLST');
        $ftp=curl_exec($curl);
        curl_close ($curl);

        $arrayFromDirFtp=preg_split("[\n|\r]",$ftp);
        foreach($arrayFromDirFtp as $dirOrFile){
            if(preg_match($pattern,$dirOrFile)){
                return $dirOrFile;
            }
        }
    }

    /**
     * Возвращает время создания файла-задания на фтп
     *
     * @param   string  $remoteFile имя удаленного файла на фтп
     *
     * @return string   $timestamp  время создания файла-задания на фтп
     */
    public  static function getTimeLastModifyFileOnFtp($remoteFile){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'ftp://80.93.48.179/'.$remoteFile);
        curl_setopt($ch, CURLOPT_USERPWD, self::getStringForCurlUserPwd());
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//чтобы exec выводился не в браузер
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_exec($ch);

        $timestamp = curl_getinfo($ch, CURLINFO_FILETIME);

        curl_close ($ch);

        return (string)$timestamp;
    }

    /**
     * удаляет файлы переданные в аргументе метода
     *
     * @param array $filesNames массив файлов на удаление
     */
    public static  function deleteOldLoadedFileForCron(array $filesNames){
        foreach($filesNames as $file){
            if(file_exists($file)){
                unlink($file);
            }
        }

    }

    /**
     * интерфейс для self::downloadAndSaveFileFromFtp,пытается скачать файл с фтп,
     * в случае не удачи записывает в логу сообщение
     *
     * @param   string  $fileNameFullPathFtp    полный путь до файла который надо скачать с ftp (вкл. имя файла)(удаленный файл)
     *
     * @param   string  $localToStreamFileNameFromFtpFullPath      полный путь до локального файла В который надо записать с ftp (вкл. имя файла) cron_price_data.txt|cron_price_data_komus.txt(локальный файл)
     *
     * @return bool
     */
    public static function tryDownloadAndSaveFileFromFtp($fileNameFullPathFtp,$localToStreamFileNameFromFtpFullPath){

        $iterator=0;//три попытки на скачивание файла
        do{
            $grabber=self::downloadAndSaveFileFromFtp($fileNameFullPathFtp,$localToStreamFileNameFromFtpFullPath);
            $iterator++;
        }while($iterator<3 && !$grabber);
        if(!$grabber){
            $pointer=($localToStreamFileNameFromFtpFullPath==self::CRON_LOAD_TXT_FILE_KOMUS)?'komus':'all';
            $bn= basename($fileNameFullPathFtp);
            $homeDirNameForTaskFile=strstr($fileNameFullPathFtp,$bn,true);//директория в которой находится файл-задание на фтп
            CronModel::CronLogger('ERROR не удалось скачать файл с '.$homeDirNameForTaskFile.' три попытки исчерпаны.Проверьте адреса и пароли указанные для CronModel::downloadAndSaveFileFromFtp',$pointer);
            return false;
        }
        return true;
    }

    /**
     * скачиваем файл с фтп
     *
     * @param   string  $fileNameFullPathFtp    имя файла который надо скачать
     *
     * @param   string $localFileName  имя файла cron_price_data.txt|cron_price_data_komus.txt
     *
     * @return bool если возвращает 1 то скачали файл
     */

    private static function downloadAndSaveFileFromFtp($fileNameFullPathFtp,$localFileName){
        $curl = curl_init();
        $file = fopen ($localFileName, 'w');
        curl_setopt($curl, CURLOPT_URL,  $fileNameFullPathFtp);
        curl_setopt($curl, CURLOPT_USERPWD, self::getStringForCurlUserPwd());

        // curl settings
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_FILE, $file);

        $result = curl_exec($curl);

        curl_close($curl);
        fclose($file);
        return $result;
    }

    /**
     * Метод возвращает строку с логином и паролем для CURLOPT_USERPWD из конф.файла
     * './config/autoload/config.json'
     *
     *@return string
     */
    public  static function getStringForCurlUserPwd(){
        $cm=ConfigModel::getConfigData('./config/autoload/config.json');
        $parentNode=$cm['ftp://80.93.48.179']['folder']['prices'];
        return $parentNode['username'].':'.$parentNode['password'];
    }








} 