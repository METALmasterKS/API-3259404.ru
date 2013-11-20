<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 16.10.13
 */

namespace RashodnikParse\Model;
//use Zend\Db\Sql\Select;
//use Zend\Db\Sql\Predicate\In;
//use Zend\Db\Sql\Predicate\Predicate;

class ModelHelpers
{
    /*подготавливаем и выполняем запрос,получаем двумерный массив,делаем из него одномерный массив,
        *это нужно для формирования JSON
        */
    public static function prepareExecuteResultFromTwoDimensionalArray($sql, $select)
    {

        $results = self::prepareExecuter($sql, $select);
        $listData = array();
        foreach ($results as $valueArr) {

            foreach ($valueArr as $value) {
                $listData[] = $value;
            }

        }

        return $listData;
    }

    /*подготавливаем и выполняем запрос,получаем одномерный массив,
    *это нужно для формирования JSON
    */

    protected static function prepareExecuter($sql, $select)
    {
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute();

        return $results;
    }

    public static function prepareExecuteSeparateObjectResult($sql, $select){

        $checker=array();
        $data=array();
        $results = self::prepareExecuter($sql, $select);
        foreach($results as $result){

           if(!in_array($result['id'],$checker)){
                $checker[]=$result['id'];
                $data[][$result['id']]=$result['p_series'];
            }


        }



        return $data;
    }

    /*подготавливаем и выполняем запрос и формируем
*массив с ключами из значений полученых в запросе
*/

    public static function prepareExecuteResultFromOneDimensionalArray($sql, $select)
    {
        $resultsPm = self::prepareExecuter($sql, $select);
        $dataPmToClient = array();
        foreach ($resultsPm as $result) {
            $dataPmToClient[] = $result;
        }

        return $dataPmToClient;
    }

    /*все тоже самое что и метод prepareExecuteResultFromOneDimensionalArray+
    изменение ключа для JSON
    $findKey-ключ который меняем
    $changerKey-ключ на который меняем
    */
    public static function prepareExecuteResultFromOneDimensionalArrayChangeKey($sql, $select,$findKey,$changerKey)
    {
        $resultsPm = self::prepareExecuter($sql, $select);
        $dataPmToClient = array();

        foreach ($resultsPm as $result) {
            $result[$changerKey] = $result[$findKey];
            unset($result[$findKey]);
            $dataPmToClient[] = $result;
        }


        return $dataPmToClient;
    }

    public static function prepareExecuteResultValueToKey($sql, $select, $keyForKey, $keyForValue)
    {

        $results = self::prepareExecuter($sql, $select);
        $dataFromDB = array();
        if (!empty($keyForKey)) {
            foreach ($results as $result) {
                /*$result[$keyForKey] из таблицы будут ключами в JSON,$result[$keyForValue] будет значениями*/
                $dataFromDB[$result[$keyForKey]] = $result[$keyForValue];
            }
        } else {
            foreach ($results as $result) {
                $dataFromDB[] = $result[$keyForValue];
            }
        }


        return $dataFromDB;
    }

    public static function prepareExecuteResultFirstRow($sql,$select,$columnResult){
        $results = self::prepareExecuter($sql, $select);
        foreach($results as $result){
            return $result[$columnResult];
        }
    }


}