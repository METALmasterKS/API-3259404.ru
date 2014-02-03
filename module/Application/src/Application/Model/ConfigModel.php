<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 03.02.14
 * Time: 11:25
 */

namespace Application\Model;


use Zend\Config\Reader\Json;

/**
 * Определяет расширение запрашиваемого конфиг файла и возвращает ноду
 * Class ConfigModel
 *
 * @package Application\Model
 */
class ConfigModel {


    public static function getConfigData($path){
        //определяет расширение конфиг файла
        $posLastPointInPath=strripos($path,'.');
        $extension=substr($path,$posLastPointInPath);

        switch($extension){
            case('.json'):
                $reader=new Json();
                return $reader->fromFile($path);
        }
    }
} 