<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 20.12.13
 */
namespace PriceParse\CronModel;

use PriceParse\FileModel\KomusFileModel;
use PriceParse\Model\TableModel;

/**
 * Class UserSelectCronModel
 * Класс отвечает за выборку из базы по файлу который был загружен пользователем пользователем
 *
 * @package PriceParse\CronModel
 */
class UserSelectCronModel{

    private $_adminModel;
    private $_fileRow;

    /**
     * @param $adminModel   адаптер из контроллера
     */
    public function __construct($adminModel){
        $this->_adminModel=$adminModel;
    }


    /**
     * метод рулевой,через него контороллер общается с UserSelectCronModel
     *
     * @param   string  $fileNameLoad   имя загруженного файла по которому осуществляется выборку
     *
     * @param   string $fileNameResult имя файла с результатом выборки из базы
     *
     * @param   integer $columnWidth кол-во колонок разделенные табуляцияей в файле
     */
    public function  recordDataForUserFileSelect($fileNameLoad,$fileNameResult,$columnWidth){
        $this->getCsv($fileNameLoad,$columnWidth);
        $this->selectFromTableWhereFileRows($fileNameResult);
    }

    /**
     * В методе из загруженного пользователем файла определяем UserSelectCronModel::_fileRow,
     * по которому осуществляется выборка из базы в UserSelectCronModel::selectFromTableWhereFileRows()
     *
     * @param   string    $fileName имя загруженного файла по которому осуществляется выборка
     *
     * @param   integer   $columnWidth  кол-во колонок разделенные табуляцияей в файле
     */
    private  function getCsv($fileName,$columnWidth){
        $dataFromFile=KomusFileModel::getCsv($fileName,$columnWidth);
        $this->_fileRow=array_chunk($dataFromFile,$columnWidth);
        if(preg_match('/\D+/',$this->_fileRow[0][0])){
            array_shift($this->_fileRow);
        }
    }

    /**
     * Метод осуществляет выборку из базы,результат через TableModel::processingAllCompetitorsResultFromDbToFile записывается в файл
     */
    private  function selectFromTableWhereFileRows($fileNameResult){
        $tableModel=new TableModel($this->_adminModel);
        if($fileNameResult==CronModel::RESULT_SELECT_TXT_ON_USER){
            foreach($this->_fileRow as $column){
                $result=$tableModel->getDataFromDbAfterCronWorkForPriceParseClientWithLoadFilePassport(CronModel::TABLE_NAME_ALL_PARSE,
                    array('id','product_1c_code','competitor_code','articul_product','price_product','product_description','status'),
                    array('product_1c_code'=>$column[0],'competitor_code'=>$column[1],'articul_product'=>$column[2]));
                $tableModel->processingAllCompetitorsResultFromDbToFile($result,$fileNameResult);
            }
        }
        if($fileNameResult==CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS){
            foreach($this->_fileRow as $column){
                $result=$tableModel->getDataFromDbAfterCronWorkForPriceParseClientWithLoadFilePassport(CronModel::TABLE_NAME_KOMUS_PARSE,
                    array('id','product_1c_code_3259404','articul_product_komus','price_product_3259404','price_product_komus','product_description'),
                    array('product_1c_code_3259404'=>$column[0],'articul_product_komus'=>$column[1],'price_product_3259404'=>$column[2]));
                //$tableModel->processingAllCompetitorsResultFromDbToFile($result,$fileNameResult);
                $tableModel->processingKomusCompetitorsResultFromDbToFile($result,$fileNameResult);
            }
        }


    }
}
