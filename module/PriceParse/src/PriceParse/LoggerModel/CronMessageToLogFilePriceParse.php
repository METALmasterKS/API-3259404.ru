<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 09.01.14
 */

namespace PriceParse\LoggerModel;


class CronMessageToLogFilePriceParse extends AbstractLoggerPriceParseStrategy{
    private $_message;
    private $_pathToFileName;

    public function __construct($item,$checkerSize){
        //$this->_pathToFileName='./data/logger/PriceParse/cron/cronKomusLogFile.log';//имя лог файла
        $this->_pathToFileName=($item=='komus')?'./data/logger/PriceParse/cron/cronKomusLogFile.log':'./data/logger/PriceParse/cron/cronAllLogFile.log';
        parent::__construct($this->_pathToFileName);//подготавливаем zf2::logger
        //если в конструктор пришел флаг на проверку размера лог файла,если больше 100 Кб то удаляет из лог файла все записи
        if(!empty($checkerSize)){
            if(filesize($this->_pathToFileName)>100000) $this->clearLogFile();
        }
    }

    /**
     * запись сообщения в логу
     *
     * @param   string  $message    сообщение
     */
    public function sendMessage($message){
        $this->_message=$message."\n\r";
        $this->_logger->info($this->_message);
    }

    /**
     * чистит лог файл от всех записей
     */
    protected  function clearLogFile(){
        file_put_contents($this->_pathToFileName, '');
        parent::__construct($this->_pathToFileName);
    }
}

