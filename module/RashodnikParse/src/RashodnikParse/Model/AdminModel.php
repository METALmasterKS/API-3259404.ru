<?php
namespace RashodnikParse\Model;

use Zend\Db\Sql\Sql;

use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use RashodnikParse\RenatsClassesLib\GetAllBrands;
use RashodnikParse\DomModel\ResourceDomModel;
use RashodnikParse\RenatsClassesLib\Db;



class AdminModel  extends AbstractTableGateway implements ServiceLocatorAwareInterface{
    public $_sql;
    protected $_serviceLocator;
    public function __construct(Sql $sql){
        $this->_sql=$sql;
    }
/*получаю все бренды с парсинга,сравниваю с значениями которые пришли из getBrandsFromDb(),чего не хватает в таблице- добавляю*/
    public function putBrandsToDbIfResourceDifference(array $brandsFromDb,$parseBrands){
            $parseBrandsFlip=array_flip($parseBrands);//в переменную попадет массив по бренду который спарсили с главной страницы http://www.rashodnika.net,меняем ключи с значениями местами,для более удобного сравнения данных из базы
            $diff=array_diff($parseBrandsFlip,$brandsFromDb);//разница между брендами из табл. printers_brands и данными по брендам с парсинга главной страницы  http://www.rashodnika.net
            if(!empty($diff)){
                foreach ($diff as $brand) {
                    $this->putInDb('printers_brands',array('p_brand'=>$brand));
                }
            }


    }
/*получаю все бренды из табл в базе*/
    public function getBrandsFromDb(){
        $select=$this->_sql->select('printers_brands');
        $dataFromDB=ModelHelpers::prepareExecuteResultValueToKey($this->_sql,$select,'','p_brand');
        return $dataFromDB;

    }
/*все типы с парсинга сравниваю со значениями которые пришли в $typesFromDb,чего не хватает в таблице- добавляю*/
    private function putTypesToDbIfResourceDifference($parse,$db){
        $diff=array_diff($parse,$db);//разница между брендами из табл. cartridges_type и данными по типам с парсинга
        if(!empty($diff)){
            foreach ($diff as $type) {
                $this->putInDb('cartridges_type',array('c_type'=>$type));
            }
        }

    }
/*получаю все типы из табл в базе*/
    private function getTypesPrintersFromDb(){
        $select=$this->_sql->select('cartridges_type');
        $dataFromDB=ModelHelpers::prepareExecuteResultValueToKey($this->_sql,$select,'','c_type');
        return $dataFromDB;
    }
/*выборка с WHERE только для одной первой записи*/
    public function getDataWhereFromDB($table,array $whereValue,$columnResult){
        $select=$this->_sql->select();
        $select->from($table)
               ->where($whereValue);
        return ModelHelpers::prepareExecuteResultFirstRow($this->_sql,$select,$columnResult);
    }




/*добавление в БД*/
    public  function putInDb($table,array $values){
        $insert=$this->_sql->insert($table);
        $insert->values($values);
        $statement = $this->_sql->prepareStatementForSqlObject($insert);
        $statement->execute();
    }
/*удаление из БД*/
    private function deleteFromDb($table,array $values){
        $delete=$this->_sql->delete($table);
        $delete->where($values);
        $statement = $this->_sql->prepareStatementForSqlObject($delete);
        $statement->execute();
    }


/*метод-шлюз для работы с ResourceDomModel*/
    public  function resourcedDomModelGateway($parseBrandsUrl){
        $href=array();
/*работа c БД ТОЛЬКО с табл.cartridges_type,сравнение типов из табл. и из парсинга,если новый тип в парсинге а в табл.его нет,то добавляем его в табл.
парсим все ссылки брендов из левого меню,результат записываем в массив $href
*/
    /*data from parsing*/
        $typesRepeatFromAllBrands=array();//массив который будет содержать все типы принтеров по всем брендам(с повторениями типов) из парсинга
        $typesFromAllBrands=array();
        $adapter=$this->_serviceLocator->get('ModelAdapter');//получение адаптера из SM
        foreach ($parseBrandsUrl as $url) {
            $dom=new ResourceDomModel('/'.$url,$adapter);
            $typesRepeatFromAllBrands[]=$dom->getTypesOfCurrentBrand();
            $href[]=$dom->getAllLinksForAllTypes();
         }
        //двумерные массивы с типами и хрефами по брендам которые получили из парсинга, делаем одномерными массивами через метод convertDoubleToSingleArray
        $typesFromAllBrands=$this->convertDoubleToSingleArray($typesRepeatFromAllBrands);
        $hrefsFromAllBrands=$this->convertDoubleToSingleArray($href);
        $typesFromAllBrands=array_unique($typesFromAllBrands);//избавляемся от повторений типов в массиве

        /*data from DB*/
        $typesFromDb=$this->getTypesPrintersFromDb();
        /*сравнение данных из парсинга и из базы по типам принтеров,если данные по типам в базе не совпадают с данными по типам с парсинга,
        то базу синхронизируем с данными из парсинга*/
        $this->putTypesToDbIfResourceDifference($typesFromAllBrands, $typesFromDb);

/*работа с внутренней структурой документа,парсинг всех таблиц совместимости...Основная работа с парсингом,добавление картриджей,
синхронизация парсинга со всеми табл. КРОМЕ  cartridges_type и printers_brands*/
        foreach ($hrefsFromAllBrands as $url) {
            $dom=new ResourceDomModel('/'.$url,$adapter);
            $dom->getAllDataFromCurrentUrl();
        }

return $parseBrandsUrl;



    }
/*конвертация двумерного массива в одномерный*/
    public function  convertDoubleToSingleArray($doubleArray){
        $singleArray=array();
        foreach($doubleArray as $arrayWrapperForDouble){
            foreach($arrayWrapperForDouble as $arrayWrapperForSingle){
                $singleArray[]=$arrayWrapperForSingle;
            }
        }
        return $singleArray;
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

    public function  getFetchRow($query){

        $adapter=$this->_serviceLocator->get('ModelAdapter');
        $db= new Db($adapter);
        $result=$db->fetchOne($query);
        return $result;
    }




}