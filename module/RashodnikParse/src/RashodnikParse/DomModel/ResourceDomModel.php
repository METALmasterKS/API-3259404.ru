<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 16.10.13
 */
namespace RashodnikParse\DomModel;

use RashodnikParse\Model\ModelHelpers;
use Zend\Db\Sql\Sql;
use Zend\Dom\Query;


use RashodnikParse\RenatsClassesLib\Db;



class ResourceDomModel{
    CONST LINK_HOME_RASHODNIKA = 'http://www.rashodnika.net';
    protected $_dom;
    protected $_adapter;
    protected $_sql;

    protected $_idBrandChecker;
    protected $_idTypeChecker;

    protected $_idLongShortCode;

    protected  $_link;
    protected  $_longCode;
    protected  $_shortCode;
    protected  $_description;

    protected $_domPrintersModelsForCurrentRow;

    public function __construct($url,$adapter){
        $this->_dom=new Query($this->getContentForUrl($url));
        $this->_adapter=$adapter;
        $this->_sql=new Sql($this->_adapter);

    }
//находит узлы которые содержат-типы принтеров  текущего бренда,вызывает метод getTextContentFromNode(),и через него получает массив с текстовыми узлами текущего бренда
    public function getTypesOfCurrentBrand(){
        $nodes=$this->_dom->execute('#sections ul li ul li ul li a');//узлы  которые содержат типы принтеров  текущего бренда
        $textContentFromNodes=$this->getTextContentFromNode($nodes);//текстовые узлы с типами принтеров для текущего бренда
        return $textContentFromNodes;
    }
//на вход узлы, на выходе- массив с ТЕКСТОВЫМИ узлами
    public function getTextContentFromNode($nodes){
        $textContent = array();
        foreach ($nodes as $node) {
            $textContent[] = $node->textContent;
        }
        return $textContent;
    }

    public function getAllLinksForAllTypes(){
        $nodes=$this->_dom->execute('#sections ul li ul li ul li a');//узлы  которые содержат типы принтеров  текущего бренда
        $href=$this->getHrefFromBrand($nodes);
        return $href;
    }
    public function getHrefFromBrand($nodes){
        $hrefs=array();
        foreach($nodes as $href){
            $hrefs[]=$href->getAttribute('href');
        }
        return $hrefs;
    }


//парсим все данные с текущего документа
public function getAllDataFromCurrentUrl(){
    $nodesHeaderFive=$this->_dom->execute('tr td h5');


           //извлекаем из заголовка название бренда и тип принтера текущего документа
           $headerFive=explode('/',$nodesHeaderFive[0]->textContent);

           $brandName=trim($headerFive[0]);//получаем строку с брендом из заголовка
           $typeName=trim($headerFive[count($headerFive)-1]);//получаем строку с типом из заголовка
    //запросы в БД
    $queryForBrandId="SELECT id FROM printers_brands WHERE p_brand ='".$brandName."'";
    $queryForTypeId="SELECT id FROM cartridges_type WHERE c_type ='".$typeName."'";
    $this->_idBrandChecker=(integer)$this->getFetchRow($queryForBrandId);//получил id из базы по текущему бренду
    $this->_idTypeChecker=(integer)$this->getFetchRow($queryForTypeId);//получил id из базы по текущему типу принтера


    /*парсинг информации из таблицы совместимости(из парсинга получим:long_number;short_number;c_description)
    *сравнение данных из парсинга и из базы по короткому и длинному коду картриджа,если данные по этим кодам в базе не совпадают с данными по кодам с парсинга,
     то базу синхронизируем с данными из парсинга,заполняем св-во $_links массивом из href внутренних страниц  каждой строки из табл.*/
           $this->getDataFromMainTableOfCompatibility();





}
/*парсинг информации из таблицы совместимости (из парсинга получим:long_number;short_number;c_description;href внутренних страниц по каждой строки из табл. )
*Делаем запрос в табл. cartridges_models,выдать id моделей картриджей по найденным long_number и short_number,если id не найден то вставляем в cartridges_models новую модель картрижда
*/
    public function getDataFromMainTableOfCompatibility(){

        $nodes=$this->_dom->execute('#listoftovar');
        $tr = $nodes[0]->getElementsByTagName('tr'); //получил все элементы tr
        $length = $tr->length; //кол-во элементов tr в документе
        //перебор строк в таблице
        for ($i = 1; $i < $length; $i++) {
            $td = $tr->item($i)->getElementsByTagName('td'); //элемент td
            $longCode = trim($td->item(1)->firstChild->textContent);
            $tdNextSibling = $td->item(1)->nextSibling->firstChild;

            $tdNextSiblingName = $tdNextSibling->nodeName;


            if ($tdNextSiblingName == 'a') {
                $shortCode = trim($tdNextSibling->textContent);
                $linkLocal = $tdNextSibling->getAttribute('href');
                $description=trim($td->item(3)->firstChild->firstChild->textContent);
            }
            else {
                $description=$tdNextSibling->firstChild->textContent;
                $shortCode = '-';
                $linkLocal = $tdNextSibling->firstChild->getAttribute('href');


            }

            //поз. последнего вхождение слова ' для'.
            $pos=strripos($description, " для");
            if($pos!==false){
                $description=substr($description,0,$pos);//вырезаем от  0 до поз. $pos
            }

            



            $links[]=str_replace(' ','%20',$linkLocal);
            $link=str_replace(' ','%20',$linkLocal);

           /* ЗАСАДА с $description -вставляет в ковычки
            $queryForLongAndShortNbrId="SELECT id FROM cartridges_models WHERE long_number ='".$longCode."' AND short_number='".$description."'";
            $this->_idLongShortCode=$this->getFetchRow($queryForLongAndShortNbrId);*/

            $this->_idLongShortCode=$this->getDataWhereFromDB('cartridges_models',array('long_number'=>$longCode,'short_number'=>$shortCode,'c_description'=>$description),'id');
            if(empty($this->_idLongShortCode)){
                $this->putInDb('cartridges_models',array('long_number'=>$longCode,'short_number'=>$shortCode,'c_description'=>$description));
                $this->_idLongShortCode=$this->getDataWhereFromDB('cartridges_models',array('long_number'=>$longCode,'short_number'=>$shortCode,'c_description'=>$description),'id');
                $this->_longCode=$longCode;
                $this->_shortCode=$shortCode;
                $this->_description=$description;
                $this->_link=$link;

                $this->getPrintersModelsFromMainTableRow();
            }

        }

    }

/*получение моделей принтеров для картриджа(строка из таблицы совместимости)*/
    private function getPrintersModelsFromMainTableRow(){

            $this->_domPrintersModelsForCurrentRow=new Query($this->getContentForUrl('/'.$this->_link));



            $nodes=$this->_domPrintersModelsForCurrentRow->execute('#resultat');
            $tdParent=$nodes[0]->parentNode;
            $ul=$tdParent->getElementsByTagName('ul');
            $ulLength=$ul->length;
            for($k=0;$k<$ulLength;$k++){
                $liNodes = $ul->item($k)->getElementsByTagName('li'); //узлы li в текущем узле ul
                $liLength=$liNodes->length;
                for($j=0;$j<$liLength;$j++){
                    $aText=$liNodes->item($j)->firstChild->textContent;
                    $series=strstr($aText,'-',true);
                    $number=str_replace('-','',strstr($aText,'-'));

                    $queryForPrinterSeriesId="SELECT id FROM printers_series WHERE p_series='".$series."'";
                    $getPrinterSeriesId=(integer)$this->getFetchRow($queryForPrinterSeriesId);
                    if(!$getPrinterSeriesId){
                        $this->putInDb('printers_series',array('p_series'=>$series));
                        $getPrinterSeriesId=(integer)$this->getFetchRow($queryForPrinterSeriesId);
                    }

                    //todo перевести в массив вне цикла а потом сверять
                    $queryForPrinterModelId="SELECT id FROM printers_models WHERE p_series=".$getPrinterSeriesId." AND p_number='".$number."' AND p_brand=".$this->_idBrandChecker." AND c_type=".$this->_idTypeChecker;
                    $getLastInsertPrinterModelId=(integer)$this->getFetchRow($queryForPrinterModelId);
                    if(!$getLastInsertPrinterModelId){
                        $this->putInDb('printers_models',array('p_series'=>$getPrinterSeriesId,'p_number'=>$number,'p_brand'=>$this->_idBrandChecker,'c_type'=>$this->_idTypeChecker));
                        //todo заменить $getLastInsertPrinterModelId на ZF2 getlastisertid
                        $getLastInsertPrinterModelId=(integer)$this->getFetchRow($queryForPrinterModelId);



                    }
                    $queryForRightSequence="SELECT id FROM right_sequence WHERE p_model=".$getLastInsertPrinterModelId." AND c_model=".$this->_idLongShortCode;
                    $getRightSequenceId=(integer)$this->getFetchRow($queryForRightSequence);
                    if(!$getRightSequenceId){
                        $this->putInDb('right_sequence',array('p_model'=>$getLastInsertPrinterModelId,'c_model'=>$this->_idLongShortCode));
                    }


                }


            }

    }


    /*добавление в БД*/
    private function putInDb($table,array $values){
        $insert=$this->_sql->insert($table);
        $insert->values($values);
        $statement = $this->_sql->prepareStatementForSqlObject($insert);
        $statement->execute();
    }


    public function  getFetchRow($query){
        $db= new Db($this->_adapter);
        $result=$db->fetchOne($query);
        return $result;
    }

    /*выборка с WHERE только для одной первой записи*/
    public function getDataWhereFromDB($table,array $whereValue,$columnResult){
        $select=$this->_sql->select();
        $select->from($table)
            ->where($whereValue);
        return ModelHelpers::prepareExecuteResultFirstRow($this->_sql,$select,$columnResult);
    }



    public  function getContentForUrl($url=''){
        return file_get_contents(self::LINK_HOME_RASHODNIKA.$url);
    }


}