<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 14.10.13
 */
namespace RashodnikParse\Model;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Predicate\Expression;
use Zend\Db\Sql\Predicate\In;
use Zend\Db\Sql\Predicate\Predicate;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;


class PrinterModel  extends AbstractTableGateway implements ServiceLocatorAwareInterface{
    protected $_adapter;
    protected $_sql;
    protected $_serviceLocator;
    public function __construct(Sql $sql){
        $this->_sql=$sql;

    }
    /*выбераем все поля из таблицы printers_brands,
     *через foreach получаем массив у которого ключи это id из таблицы,
     * значения это название бренда,делаем это для передачи в метод создания формы
     * setValueOptions()
     * */
    public function getBrands(){
/*        $select=$this->_sql->select('printers_brands');
        $dataFromDB=ModelHelpers::prepareExecuteResultValueToKey($this->_sql,$select,'id','p_brand');
        return $dataFromDB;*/
        $select=$this->_sql->select('printers_brands')
            ->order('p_brand ASC');
       return ModelHelpers::prepareExecuteResultFromOneDimensionalArrayChangeKey($this->_sql,$select,'p_brand','name');

    }

    public function getPrinterTypes($id){
        /*запросы в printers_models table*/
        /*подзапрос*/
        $subSelect=$this->_sql->select('printers_models')
            ->where(array('p_brand'=>$id))
            ->columns(array('c_type'));
        $subSelectResult=ModelHelpers::prepareExecuteResultFromTwoDimensionalArray($this->_sql,$subSelect);
        /*главный запрос*/
        $mainSelect=$this->_sql->select('cartridges_type')
            ->where(array(
                    new Predicate(
                        array(
                        new In('id',$subSelectResult)
                        )
                    ),
                    ))
            ->columns(array('id','c_type'))->order('c_type ASC');
       // $dataFromDB=ModelHelpers::prepareExecuteResultValueToKey($this->_sql,$mainSelect,'id','c_type');
       // return $dataFromDB;
        return  ModelHelpers::prepareExecuteResultFromOneDimensionalArrayChangeKey($this->_sql,$mainSelect,'c_type','name');
    }


    /*выполняем запрос к таблице printers_models только по бренду*/
    public function getModelsOnlyForBrand($idBrand){
       /* $select=$this->_sql->select('printers_models')
            ->join(array('printers_series'=>'printers_series'),'printers_series.id=printers_models.p_series',array('p_series'))
            ->where(array('p_brand'=>$idBrand))
            ->columns(array('id','p_series','p_number'))
            ->order('printers_series.p_series ASC')
            ->order('printers_models.p_number ASC');*/
        // return ModelHelpers::prepareExecuteResultFromOneDimensionalArray($this->_sql,$select);
        $query="SELECT DISTINCT  printers_models.id,
        printers_series.p_series,
        printers_models.p_number
        FROM  printers_models
        INNER JOIN printers_series ON printers_series.id=printers_models.p_series
        WHERE printers_models.p_brand=? ORDER BY printers_series.p_series,CONVERT(printers_models.p_number,SIGNED) ASC,printers_models.p_number ASC";
        //$results=$this->getDirtyQuery($query,array($idBrand));
        $results = $this->_serviceLocator->get('ModelAdapter')->query($query,array($idBrand));
        $dataPmToClient = array();
        foreach ($results as $result) {
            $dataPmToClient[] = $result;
        }

        return $dataPmToClient;

    }

    /*выполняем запрос к таблице printers_models по бренду и типу*/
    public function getPrecisionModelsForType($idBrand,$idType){
       /* $select=$this->_sql->select('printers_models')
            ->join(array('printers_series'=>'printers_series'),'printers_series.id=printers_models.p_series',array('p_series'))
            ->where(array('p_brand'=>$idBrand))
            ->where(array('c_type'=>$idType))
            ->columns(array('id','p_series','p_number'))
            ->order('printers_series.p_series ASC');
        return ModelHelpers::prepareExecuteResultFromOneDimensionalArray($this->_sql,$select);*/
        $query="SELECT DISTINCT  printers_models.id,
        printers_series.p_series,
        printers_models.p_number
        FROM  printers_models
        INNER JOIN printers_series ON printers_series.id=printers_models.p_series
        WHERE printers_models.p_brand=? AND printers_models.c_type=? ORDER BY printers_series.p_series,CONVERT(printers_models.p_number,SIGNED) ASC,printers_models.p_number ASC";
       // $results=$this->getDirtyQuery($query);
        $results = $this->_serviceLocator->get('ModelAdapter')->query($query,array($idBrand,$idType));
        $dataPmToClient = array();
        foreach ($results as $result) {
            $dataPmToClient[] = $result;
        }

        return $dataPmToClient;
    }



    /*выполняем запрос к таблице printers_models по бренду,типу и серии принтера*/
    public function getPrecisionModelsForSeries($idBrand,$idType,$idSeries){
       /* $select=$this->_sql->select('printers_models')
            ->where(array('p_brand'=>$idBrand))
            ->where(array('p_series'=>$idSeries))
            ->columns(array('id','p_number'))
            ->order('p_number ASC');
	if (isset($idType))
	    $select->where(array('c_type'=>$idType));
	
        return ModelHelpers::prepareExecuteResultFromOneDimensionalArray($this->_sql,$select);*/
        $query="SELECT printers_models.id,printers_models.p_number FROM printers_models WHERE printers_models.p_brand=? AND printers_models.p_series=?";
        $params=array($idBrand,$idSeries);
        if (isset($idType)){
            $query.=" AND printers_models.c_type=?";
            array_push($params,$idType);
        }
        $query.=" ORDER by CONVERT (printers_models.p_number, SIGNED) ASC,printers_models.p_number ASC";
        //$results=$this->getDirtyQuery($query);
        $results = $this->_serviceLocator->get('ModelAdapter')->query($query,$params);
        $dataPmToClient = array();
        foreach ($results as $result) {
            $dataPmToClient[] = $result;
        }

        return $dataPmToClient;
    }



    /*выполняем запрос к базе-выдать нужный картридж по модели*/
    public function getCartridgesForModel($idModel){
        $select = $this->_sql->select();
        $select->from('right_sequence')
            ->join(array('printers_models' => 'printers_models'), 'printers_models.id=right_sequence.p_model',array())
            ->join(array('cartridges_models' => 'cartridges_models'), 'cartridges_models.id=right_sequence.c_model',array('c_description','long_number','short_number'))
            ->where(array(
                    new Predicate(
                        array(
                        new In('p_model',array($idModel))
                        )
                    )
                    ))->columns(array());
        return ModelHelpers::prepareExecuteResultFromOneDimensionalArray($this->_sql,$select);
    }
    /*выполняем запрос к табл.printers_models выдать серии принтеров по бренду(id)*/
    /**
    SELECT  DISTINCT printers_series.p_series
    FROM  printers_models
    INNER JOIN printers_series ON printers_series.id=printers_models.p_series
    WHERE p_brand=4 ORDER BY printers_series.p_series  ASC
     */
    public function  getSeriesOnlyForBrand($id){
        $select = $this->_sql->select();
        $select->from('printers_models')
            ->join(array('printers_series'=>'printers_series'),'printers_series.id=printers_models.p_series',array('id','p_series'))
            ->where(array('p_brand'=>$id))
            ->columns(array('id','p_series'))
            ->order('printers_series.p_series ASC');
        $select->columns(array(new Expression('DISTINCT(printers_series.id) as id')));
        //return ModelHelpers::prepareExecuteResultValueToKey($this->_sql,$select,'id','p_series');
        return ModelHelpers::prepareExecuteResultFromOneDimensionalArrayChangeKey($this->_sql,$select,'p_series','name');

    }

    /*выполняем запрос к табл.printers_models выдать серии принтеров по бренду(id) и типу idType*/
    public function getPrecisionSeriesForType($id,$idType){
        $select = $this->_sql->select();
        $select->from('printers_models')
            ->join(array('printers_series'=>'printers_series'),'printers_series.id=printers_models.p_series',array('id','p_series'))
            //->quantifier('DISTINCT')
            ->where(array('p_brand'=>$id))
            ->where(array('c_type'=>$idType))
            ->columns(array('id','p_series'))
            ->order('printers_series.p_series ASC');
        $select->columns(array(new Expression('DISTINCT(printers_series.id) as id')));
//        return ModelHelpers::prepareExecuteResultValueToKey($this->_sql,$select,'id','p_series');
        return ModelHelpers::prepareExecuteResultFromOneDimensionalArrayChangeKey($this->_sql,$select,'p_series','name');
    }

    /*получение моделей принтеров по long_number картриджей*/
    public function getPrecisionPrintersForLongNumber($longNum){
        $select = $this->_sql->select();
        $select->from('printers_models')
            ->join(array('right_sequence'=>'right_sequence'),'printers_models.id=right_sequence.p_model',array())
            ->join(array('printers_series'=>'printers_series'),'printers_models.p_series=printers_series.id',array('p_series'))
            ->join(array('printers_brands'=>'printers_brands'),'printers_models.p_brand=printers_brands.id',array('p_brand'))
            ->join(array('cartridges_type'=>'cartridges_type'),'printers_models.c_type=cartridges_type.id',array('c_type'))
            ->join(array('cartridges_models'=>'cartridges_models'),'right_sequence.c_model=cartridges_models.id',array())
            ->where(array('cartridges_models.long_number'=>$longNum))
            ->columns(array('id','p_number'));

        return ModelHelpers::prepareExecuteResultFromOneDimensionalArray($this->_sql,$select);
    }



    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->_serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->_serviceLocator;
    }
/*голый запрос через адаптер*/
    public function  getDirtyQuery($query,Array $params){

        $adapter=$this->_serviceLocator->get('ModelAdapter');
      //  $statement = $adapter->createStatement($query);
        $statement = $adapter->query($query,$params);
        $statement->prepare();
        return $statement->execute();
    }

    private function returnAdapter(){
        $adapter=$this->_serviceLocator->get('ModelAdapter');
        return $adapter;
    }
}
