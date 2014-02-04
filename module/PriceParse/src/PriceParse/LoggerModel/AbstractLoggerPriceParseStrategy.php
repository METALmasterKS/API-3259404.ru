<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 27.12.13
 */

namespace PriceParse\LoggerModel;


use Zend\Log\Logger;
use Zend\Log\Writer\Stream;

abstract class AbstractLoggerPriceParseStrategy {
    protected $_logger;

    public function __construct($pathAndFileName)
    {
        $this->_logger = new Logger();
        //$writer = new Stream('./data/logger/PriceParse/cron/cronLogFile.log');
        $writer = new Stream($pathAndFileName);
        $this->_logger->addWriter($writer);
    }



    abstract public function sendMessage($message);
} 