<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 13.12.13
 */
namespace PriceParse\Model;

use PriceParse\CronModel\CronModel;
use PriceParse\DomModel\SourceFabricaDomModel;
use PriceParse\FileModel\CronAllFileModel;
use PriceParse\FileModel\FileModel;
use PriceParse\FileModel\KomusFileModel;
use RashodnikParse\Model\AdminModel;
use RashodnikParse\Model\ModelHelpers;

use Zend\Db\Sql\Expression;
use Zend\ServiceManager\ServiceLocatorInterface;


class TableModel {

protected $_serviceLocator;
private $_arrayFromFile;
public $_adminDb;
public $count;



    /**
     * определяет св-во  $this->_adminDb для манипулирования с БД
     *
     * @param  $adminModelInstance     экземпляр класса  RashodnikParse\Model\AdminModel
     * @param string     $rule                   правило работы с БД
     * @param string     $fluence                выбор таблицы,по умолчанию  таблица 'parse_competitors_prices' которая работает на парсинг всех конкурентов
     */

    public function __construct($adminModelInstance,$rule='',$fluence=''){
        $this->_adminDb=$adminModelInstance;
        switch($rule){
            case('create'):$this->prepareArrayFromFileTxtForInsertIntoDb(FileModel::LOAD_TXT_FILE,$fluence);break;
            case('create_komus'):$this->prepareArrayFromFileTxtForInsertIntoDb(FileModel::LOAD_TXT_FILE_KOMUS,$fluence);break;
            case('create_crone_table'):$this->prepareArrayFromFileTxtForInsertIntoDb(CronModel::CRON_LOAD_TXT_FILE,$fluence);break;
            case('create_crone_komus_table'):$this->prepareArrayFromFileTxtForInsertIntoDb(CronModel::CRON_LOAD_TXT_FILE_KOMUS,$fluence);break;
           // case('insert_parse_data'):$this->updateDataInDBWhere($where,$set);break;
            case('dump_table'):$this->dumpFile($fluence);break;
            case('dump_table_komus'):$this->dumpFile($fluence);break;
        }

    }

    public function dumpFile($fluence){
        $select=$this->_adminDb->_sql->select();
        if($fluence=='komus'){
            $select->from('parse_komus_diff_prices')
                ->columns(array('id','product_1c_code_3259404','articul_product_komus','price_product_3259404','price_product_komus','product_description'));
            $resultsParseFromTable= ModelHelpers::prepareExecuter($this->_adminDb->_sql,$select);
            //заголовки столбцов
          //  $header = "Номер строки в базе" . "\t" . "Код товара по 1c" . "\t" . "Артикул конкурента" . "\t" . "Цена продажи наша" ."\t" . "Цена продажи комуса" ."\t" . "Статус цены" . "\t" . "Важность" . "\t" . "Описание" . "\n";
           // SourceFabricaDomModel::spitResultFileOut($header);
            foreach ($resultsParseFromTable as $result) {

                $resultString=$this->calculation($result);
                if(!$resultString){
                    continue;
                }
//                SourceFabricaDomModel::spitResultFileOut($resultString,FileModel::TXT_OLD_FILE_KOMUS);
                SourceFabricaDomModel::spitResultFileOut($resultString,CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS);
            }
        }
        else{
            $chooseTableforAllCompetitors=($fluence=='cron-all')?'parse_competitors_prices_cron':'parse_competitors_prices';
            $select->from($chooseTableforAllCompetitors)
                ->columns(array('id','product_1c_code','competitor_code','articul_product','price_product','product_description'));
            $resultsParseFromTable= ModelHelpers::prepareExecuter($this->_adminDb->_sql,$select);
            //заголовки столбцов
           // $header = "Номер строки в базе" . "\t" . "Код товара по 1c" . "\t" . "Код конкурента" . "\t" . "Артикул конкурента" . "\t" . "Цена продажи" . "\t" . "Описание" . "\n";
            //SourceFabricaDomModel::spitResultFileOut($header);
            foreach ($resultsParseFromTable as $result) {
                //		 $price=iconv("utf-8", "windows-1251", $price);
//		 $label=iconv("utf-8", "windows-1251", $label);
                $isKomusFromTech=$this->descriptionConverter($result['product_description']);
                if($isKomusFromTech) $result['product_description']=$isKomusFromTech;
                $resultString=$result['product_1c_code']."\t".$result['competitor_code']."\t".$result['articul_product']."\t".$result['price_product']."\t".$result['product_description']."\t".$result['id']."\n";
                //$resultString=iconv("utf-8", "windows-1251", $resultString);
                $resultString=charset_x_win($resultString);
                if($fluence=='cron-all'){
                    SourceFabricaDomModel::spitResultFileOut($resultString,CronModel::RESULT_SELECT_TXT_ON_USER);
                }
                else{
                    SourceFabricaDomModel::spitResultFileOut($resultString);
                }

            }
        }

       // $this->clearTableFromParseData('parse_komus_diff_prices');

        /**
         * SELECT product_1c_code,competitor_code,articul_product,price_product,product_description
        FROM parse_competitors_prices  INTO OUTFILE 'c:\\test.txt' FIELDS TERMINATED BY '\t' OPTIONALLY ENCLOSED BY '"' ESCAPED BY '"' LINES TERMINATED BY '\r\n'
         */
       // $sql="SELECT `product_1c_code` FROM `parse_competitors_prices`";
//        $sql="SELECT product_1c_code,competitor_code,articul_product,price_product,product_description FROM parse_competitors_prices  INTO OUTFILE '{$_SERVER['DOCUMENT_ROOT']}/../data/result.txt' CHARACTER SET cp1251  FIELDS TERMINATED BY '\t' OPTIONALLY ENCLOSED BY '\"' ESCAPED BY '\"' LINES TERMINATED BY '\r\n'";
//       // $this->_adminDb->query($sql);
//        $statement = $this->_adminDb->createStatement($sql);
//        $statement->execute();
//        $this->clearTableFromParseData();

    }

    public function processingAllCompetitorsResultFromDbToFile($resultsParseFromTable,$fileRecorderName){
        foreach ($resultsParseFromTable as $result) {
            $isKomusFromTech=$this->descriptionConverter($result['product_description']);
            if($isKomusFromTech) $result['product_description']=$isKomusFromTech;
            $resultString=$result['product_1c_code']."\t".$result['competitor_code']."\t".$result['articul_product']."\t".$result['price_product']."\t".$result['product_description']."\t".$result['status']."\t".$result['id']."\n";
            $resultString=iconv("utf-8", "windows-1251", $resultString);
            SourceFabricaDomModel::spitResultFileOut($resultString,$fileRecorderName);
        }
    }

    public function processingKomusCompetitorsResultFromDbToFile($resultsParseFromTable,$fileRecorderName){
        foreach ($resultsParseFromTable as $result) {
            $isKomusFromTech=$this->descriptionConverter($result['product_description']);
            if($isKomusFromTech) $result['product_description']=$isKomusFromTech;
            $resultString=$result['product_1c_code_3259404']."\t".$result['articul_product_komus']."\t".$result['price_product_3259404']."\t".$result['price_product_komus']."\t".$result['product_description'].$result['id']."\n";
            $resultString=iconv("utf-8", "windows-1251", $resultString);
            SourceFabricaDomModel::spitResultFileOut($resultString,$fileRecorderName);
        }
    }

    private function descriptionConverter($dirtyDescription){
        $pos=strpos($dirtyDescription,"FLAG-TECH-KOMUS-");
        if($pos!==false){

            $dirtyDescription=str_replace('FLAG-TECH-KOMUS-','',$dirtyDescription);
            $dirtyDescription=FileModel::to_norm_utf8($dirtyDescription);
            return $dirtyDescription;

        }

        return false;
    }

    private function calculation(array $result){
        $ourPrice=$result['price_product_3259404'];
        if(preg_match('/\d{1,}/',$result['price_product_komus']) && isset($result['price_product_komus'])){
            $ourPrice=(integer)$ourPrice;
            $komusPrice=(integer)str_replace(' ','',$result['price_product_komus']);

            if($ourPrice != $komusPrice){
                if($ourPrice!=0){
                    $procentDiff=100-round((($komusPrice*100)/$ourPrice),1);
                }
                else{
                    $procentDiff=100;
                }

                if($ourPrice > $komusPrice){
                    $statusPrice="выше цены комуса на ".$procentDiff."%";

                }
                else{
                    return false;
                }
            }
            else{
                return false;
            }
        }
        else{
            return false;
        }
        $isKomusFromTech=$this->descriptionConverter($result['product_description']);

        if($isKomusFromTech){
            $result['product_description']= $isKomusFromTech;
            $result['price_product_komus']=trim(str_replace(' ','',preg_replace('/[,]+/', '.', $result['price_product_komus'])));
        }

        $result['price_product_3259404']=chr(32).$result['price_product_3259404'];
        $result['product_description']=preg_replace('/[,.]+/', '', trim(preg_replace('/\t+/', '', $result['product_description'])));
        $resultString=$result['product_1c_code_3259404']."\t".$result['articul_product_komus']."\t".$result['price_product_3259404']."\t".$result['price_product_komus']."\t".$statusPrice."\t".$result['product_description']."\t".$result['id']."\n";

        $resultString=charset_x_win($resultString);
        return $resultString;
    }

    public function clearTableFromParseData($table='parse_competitors_prices'){
//        $sql="DELETE FROM parse_competitors_prices";
//        $statement = $this->_adminDb->createStatement($sql);
//        $statement->execute();
        //FileModel::managerFilesConverter();//удаляем  тхт с которого получили данные
       // unlink(FileModel::LOAD_TXT_FILE);
        $delete=$this->_adminDb->_sql->delete();
        $delete->from($table);
        ModelHelpers::prepareExecuter($this->_adminDb->_sql,$delete);

    }




    public function makeIdOne($table='parse_competitors_prices'){
        $sql="ALTER TABLE ".$table." AUTO_INCREMENT =1";
        $this->_adminDb->query($sql);
        $statement = $this->_adminDb->createStatement($sql);
        $statement->execute();
    }



    /**
     * public static function prepareExecuteResultFromOneDimensionalArray($sql, $select)
    {
    $resultsPm = self::prepareExecuter($sql, $select);
    $dataPmToClient = array();
    foreach ($resultsPm as $result) {
    $dataPmToClient[] = $result;
    }

    return $dataPmToClient;
    }
     */


    /**
     * Метод подготавливает массив полученный из TXT файла, для вставки в БД,
     * через метод $this->putInDbTableModelAdapter() вставляет в БД
     *
     * @param $inputFileName
     * @param $fluence
     */
    public  function prepareArrayFromFileTxtForInsertIntoDb($inputFileName,$fluence){
        if($fluence=='komus'){
            $this->_arrayFromFile=KomusFileModel::komusTxtDataToTwoDimensionalArray($inputFileName,3);


        }
        else{
            $this->_arrayFromFile=FileModel::txtDataToTwoDimensionalArray($inputFileName);
            array_shift($this->_arrayFromFile);//удаление названия столбцов
        }
        $this->count=count($this->_arrayFromFile);
        $this->putInDbTableModelAdapter($fluence);

    }

    public function putInDbTableModelAdapter($fluence){
        $tableNameForAllCronOrNot=(CronAllFileModel::$allSwitcher=='on')?'parse_competitors_prices_cron':'parse_competitors_prices';
        foreach ($this->_arrayFromFile as $string) {
            if($fluence=='komus'){
                $this->_adminDb->putInDb('parse_komus_diff_prices',array('product_1c_code_3259404'=>$string[0],'articul_product_komus'=>$string[1],'price_product_3259404'=>$string[2]));

            }
            else{
                if($string[1]=='17404'){
                    $string[2]="K".substr($string[2],1);
                }
                $this->_adminDb->putInDb($tableNameForAllCronOrNot,array('product_1c_code'=>$string[0],'competitor_code'=>$string[1],'articul_product'=>$string[2]));
            }

        }
    }



    public static function putInDbDataFromTxtFileTableModelAdapter($inputFileName){
        self::prepareArrayFromFileTxtForInsertIntoDb($inputFileName,'');
    }



    public static function updateInDbForParseData(array $where,array $set,$table='parse_competitors_prices'){
       // self::updateDataInDBWhere($where,$update);
        $update=FileModel::$tableModel->_adminDb->_sql->update($table);
        $update->set($set)
            ->where($where);
        $statement = FileModel::$tableModel->_adminDb->_sql->prepareStatementForSqlObject($update);
        $statement->execute();
    }
//todo вроде мертвый метод нигде не использую
    public function updateDataInDBWhere(array $where, array $set){
        /**
         * UPDATE table_name
        SET column1=value1,column2=value2,...
        WHERE some_column=some_value;
         */
        $update=$this->_adminDb->_sql->update('parse_competitors_prices');
        $update->set($set)
               ->where($where);
        $statement = $this->_adminDb->_sql->prepareStatementForSqlObject($update);
        $statement->execute();
//        $update->where($where);
//        $update->set($set);
    }

    public function getDataFromDBForPriceParseWithLimitWhereStatusOff($table='parse_competitors_prices',array $columns=array('product_1c_code','competitor_code','articul_product')){
        $select=$this->_adminDb->_sql->select();
        $select->from($table)
               ->columns($columns)
               ->where(array('status' => 'not'))
               ->limit(50);
        $arrayToResult=array();
        $commonResultArray=ModelHelpers::prepareExecuteResultFromOneDimensionalArray($this->_adminDb->_sql,$select);
        foreach($commonResultArray as $block){
            $arrayToResult[]=array($block);
        }
        return $arrayToResult;


    }

    public function getDataFromDbAfterCronWorkForPriceParseClientWithLoadFilePassport($table,array $columns,array $where){
        $select=$this->_adminDb->_sql->select();
        $select->from($table)
            ->columns($columns)
            ->where($where);
        return ModelHelpers::prepareExecuter($this->_adminDb->_sql,$select);
    }

    /**
     * возвращает count таблицы
     *
     * @param   string    $table    имя таблицы
     */
    public function getCountTableFromDb($table){
        $select=$this->_adminDb->_sql->select();
        $select->from($table)
            ->columns(array('id' => new Expression('COUNT(*)')));

        $count=ModelHelpers::prepareExecuter($this->_adminDb->_sql,$select);
        foreach($count as $val){
            //  var_dump($val) ;
            return $val['id'];
        }
    }







}

