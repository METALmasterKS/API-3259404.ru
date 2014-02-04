<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 26.12.13
 */

namespace PriceParse\FileModel;


use PriceParse\CronModel\CronModel;
use PriceParse\Model\TableModel;

class CronKomusFileModel {

    private $_fileNameKomusSpbFtp;//имя нужного файла найденного на фтп копуса который надо скачать
    private $_adminModel;
    CONST FTP_325_ADDRESS_KOMUS_PRICE_ITEMS="ftp://80.93.48.179/prices/";

    public function __construct($adminModel){
        CronModel::CronLogger('--------------------крон запущен на скачку файла комуса с фтп---------------------------','komus','check');//записываем в логу о начале скачки файла комуса с нашего фтп,проверка на размер лог-файла.
        $this->deleteOldLoadedFileForCron(array(CronModel::CRON_LOAD_TXT_FILE_KOMUS));//на всяк случай
        $this->_adminModel=$adminModel;
        $downloadResult=$this->downloadKomusPriceItemsFromFtp();
        if($downloadResult){
            $this->createKomusMaskInTableForCron($this->_adminModel);//вставляем
            CronModel::CronLogger('файл '.$this->_fileNameKomusSpbFtp.' успешно скопирован с фтп и по файлу сделан слепок в БД','komus');
            //удаляем CronModel::CRON_LOAD_TXT_FILE_KOMUS, результат уже в базе
            $this->deleteOldLoadedFileForCron(array(CronModel::CRON_LOAD_TXT_FILE_KOMUS));

        }
    }

    /**
     * определяем имя TXT файла в директории FTP который надо скачать,скачиваем txt файла КОМУСА с FTP 325
     */
    public function downloadKomusPriceItemsFromFtp(){
     //  $this->getTodayFileNameFromFtp();//определяем CronKomusFileModel::_fileNameKomusSpbFtp
        $this->_fileNameKomusSpbFtp=self::getTodayFileNameFromFtp();//определяем CronKomusFileModel::_fileNameKomusSpbFtp
        if(isset($this->_fileNameKomusSpbFtp)){
            $iterator=0;//три попытки на скачивание файла
            do{
                $grabber=$this->downloadAndSaveFileFromFtp($this->_fileNameKomusSpbFtp,CronModel::CRON_LOAD_TXT_FILE_KOMUS);
                $iterator++;
            }while($iterator<3 && !$grabber);
            if(!$grabber){
                //todo записать в логу что не удалось скачать файл
                CronModel::CronLogger('ERROR не удалось скачать файл с '.self::FTP_325_ADDRESS_KOMUS_PRICE_ITEMS.$this->_fileNameKomusSpbFtp.' три попытки исчерпаны.Проверьте адреса и пароли указанные для CronKomusFileModel::downloadAndSaveFileFromFtp','komus');
                return false;
            }

        }
        else{
            //todo записать в логу что либо сменили название файла который надо скачать либо файл не обновляли на фтп
            CronModel::CronLogger('ERROR не удалось скачать файл с '.self::FTP_325_ADDRESS_KOMUS_PRICE_ITEMS.' сменили название файла на ftp.','komus');
            return false;
        }
        return true;
    }

    /**
     * получает имя файла с FTP который надо скачать
     * выбираем имя файла в котором есть хоть одна цифра
     */
    private static function getTodayFileNameFromFtp(){
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,  self::FTP_325_ADDRESS_KOMUS_PRICE_ITEMS);
        curl_setopt($curl, CURLOPT_USERPWD, CronModel::getStringForCurlUserPwd());
        curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1) ;
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'NLST');
        $ftp=curl_exec($curl);
        curl_close ($curl);

        $arrayFromDirFtp=preg_split("[\n|\r]",$ftp);
        foreach($arrayFromDirFtp as $dirOrFile){
            if(preg_match('/^Komus_\d{1,}/',$dirOrFile)){
                return $dirOrFile;
            }
        }
    }

    /**
     * скачиваем файл с фтп
     *
     * @param   string  $fileNameKomusSpbFtp    имя файла который надо скачать
     *
     * @param   string $localFileNameKomus  имя файла cron_price_data.txt|cron_price_data_komus.txt
     *
     * @return bool если возвращает 1 то скачали файл
     */

    private function downloadAndSaveFileFromFtp($fileNameKomusSpbFtp,$localFileNameKomus){
        $curl = curl_init();
        $file = fopen ($localFileNameKomus, 'w');
        curl_setopt($curl, CURLOPT_URL,  self::FTP_325_ADDRESS_KOMUS_PRICE_ITEMS.$fileNameKomusSpbFtp);
        curl_setopt($curl, CURLOPT_USERPWD, CronModel::getStringForCurlUserPwd());

        // curl settings
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_FILE, $file);

        $result = curl_exec($curl);
        
        curl_close($curl);
        fclose($file);
        return $result;
    }

    /**
     *подготавливаем таблицу для парсинга с комуса
     *
     * @param $adminModel
     */
    private function createKomusMaskInTableForCron($adminModel){
        $tableModel=new TableModel($adminModel,'create_crone_komus_table','komus');//-создание комуса

    }

    private function deleteOldLoadedFileForCron(array $filesNames){
        foreach($filesNames as $file){
            if(file_exists($file)){
                unlink($file);
            }
        }

    }
    /**
     * Переписываем данные из $localfile в $remoteFile
     *
     * @param   string  $localfile  имя локального файла
     *
     * @param   string  $remoteFile имя удаленного файла на фтп
     */
    public static  function sendResultFileOnFtp($localfile,$remoteFile){
        $ch = curl_init();
        $fp = fopen($localfile, 'r');
        curl_setopt($ch, CURLOPT_URL, 'ftp://80.93.48.179/'.$remoteFile);
        curl_setopt($ch, CURLOPT_USERPWD, CronModel::getStringForCurlUserPwd());
        curl_setopt($ch, CURLOPT_UPLOAD, 1);
        curl_setopt($ch, CURLOPT_INFILE, $fp);
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($localfile));
        curl_exec ($ch);
        curl_close ($ch);
        fclose($fp);
    }

    /**
     * Возвращает время создания файла-задания на фтп
     *
     * @param $remoteFile
     *
     * @return string   $timestamp  время создания файла-задания на фтп
     */
    private static function getTimeLastModifyFileOnFtp($remoteFile){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'ftp://80.93.48.179/'.$remoteFile);
        curl_setopt($ch, CURLOPT_USERPWD, CronModel::getStringForCurlUserPwd());
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//чтобы exec выводился не в браузер
        curl_setopt($ch, CURLOPT_FILETIME, true);
        curl_exec($ch);

        $timestamp = curl_getinfo($ch, CURLINFO_FILETIME);

        curl_close ($ch);
       // echo $timestamp;
        return (string)$timestamp;
    }

    /**
     * Проверка последней версии файла-парсинг-задания на фтп
     *
     * @param $adminModel   адаптер
     *
     * @param   string  $path   путь до лог-файла(lastModifyKomus.log) в котором лежит timestamp последней модификации  файла-парсинг-задания на фтп
     *
     * @return bool
     */
    public static function checkFileKomusLastModify($adminModel,$path){
        if(file_exists($path)){
            //$stringFromfile=file_get_contents($path);//время создания файла по которому был произведен парсинг в прошлый раз,читаем из lastModifyKomus.log
            if(filesize($path)==0){
				//$stringFromfile="";//todo можно удалить так же как и строку if($stringFromfile===$timeLastModify)
            $remoteFile="prices/".self::getTodayFileNameFromFtp();//имя файла с фтп
            
			 $attempt=0;//три попытки на получение времяни создания файла-задания на ФТП(для обрыва соединения)
            do{
                $timeLastModify=self::getTimeLastModifyFileOnFtp($remoteFile);
                $attempt++;
            }while($attempt<3 && $timeLastModify=='-1');
            if($timeLastModify=='-1'){
                CronModel::CronLogger('WARNING!!!Не удалось получить дату создания файла-задания с ФТП.','komus');
                return false;
            }
          //  if($stringFromfile===$timeLastModify) return false;
            $cronModel= new CronModel($adminModel);
            $statusMessage=$cronModel->setStatusAnswer('komus');//получаю статус таблицы в БД
            if($statusMessage==CronModel::TABLE_NAME_KOMUS_PARSE.' table is empty'){
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
			return false;
        }
		return false;
    }
//работающий вариант,парсит когда дата изменения файла задания изменилась 	
	 public static function checkFileKomusLastModify1($adminModel,$path){
        if(file_exists($path)){
            $stringFromfile=file_get_contents($path);//время создания файла по которому был произведен парсинг в прошлый раз,читаем из lastModifyKomus.log
            $remoteFile="prices/".self::getTodayFileNameFromFtp();//имя файла с фтп
            //$timeLastModify=self::getTimeLastModifyFileOnFtp($remoteFile);
			 $attempt=0;//три попытки на получение времяни создания файла-задания на ФТП(для обрыва соединения)
            do{
                $timeLastModify=self::getTimeLastModifyFileOnFtp($remoteFile);
                $attempt++;
            }while($attempt<3 && $timeLastModify=='-1');
            if($timeLastModify=='-1'){
                CronModel::CronLogger('WARNING!!!Не удалось получить дату создания файла-задания с ФТП.','komus');
                return false;
            }
            if($stringFromfile===$timeLastModify) return false;
            $cronModel= new CronModel($adminModel);
            $statusMessage=$cronModel->setStatusAnswer('komus');//получаю статус таблицы в БД
            if($statusMessage==CronModel::TABLE_NAME_KOMUS_PARSE.' table is empty'){
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
	
	
	
	public static function clearFileKomusLastModify($path){
		file_put_contents($path,'');
	
	}

} 