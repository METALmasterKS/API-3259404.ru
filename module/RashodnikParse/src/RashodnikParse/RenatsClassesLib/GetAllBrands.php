<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 08.10.13
 */
namespace RashodnikParse\RenatsClassesLib;



abstract class ParseCartridges
{
    protected static $_dom; //все  бренды принтеров с http://www.rashodnika.net

    public function __construct($url = '')
    {
        $page = file_get_contents(CartridgesMemory::LINK_HOME_RASHODNIKA . $url);
        self::$_dom = new \DOMDocument('1.0', 'UTF-8');
        self::$_dom->loadHTML($page);
    }



    abstract public function readInfoFromBrands();

    abstract public function parseDataToXMLFiles();

    abstract public function getResult();
}

class CartridgesMemory
{
    CONST LINK_HOME_RASHODNIKA = 'http://www.rashodnika.net';

    /*получаю элемент четвертой вложенности по указанным элементам массива в $aimElements(см.описание GetAllBrands::readInfoFromBrands)*/

    public static function getElementWithId($_page, Array $aimElements)
    {
        list($zeroDepthElements, $zeroDepthElementIdArr, $firstDepthElements, $secondDepthElements, $goalElements)
            = $aimElements;
        $zeroDepthElementsSearchId = $zeroDepthElementIdArr['id'];
        $zeroDepthElementsCollection = $_page->getElementsByTagName($zeroDepthElements);

        foreach ($zeroDepthElementsCollection as $zeroDepthElement) {
            $attr = $zeroDepthElement->getAttribute('id');
            if ($attr == $zeroDepthElementsSearchId) {
                $brandArray = array();
                $firstDepthElementsCollection = $zeroDepthElement->getElementsByTagName(
                    $firstDepthElements
                ); //получил все элементы которые в поиске внутри элемента с найденым id
                // $length = $firstDepthElementsCollection->length; //кол-во элементов документе
                /*надо в отдельный метод*/
                foreach ($firstDepthElementsCollection as $firstDepthElement) {
                    $secondDepthElementsCollection = $firstDepthElement->getElementsByTagName($secondDepthElements);
                    foreach ($secondDepthElementsCollection as $secondDepthElement) {
                        $goalElement = $secondDepthElement->getElementsByTagName($goalElements);
                        foreach ($goalElement as $element) {
                            $brandArray[$element->textContent] = $element->getAttribute('href');
                        }
                    }
                }

                return $brandArray;
                break;
            }
        }
    }


}

class GetAllBrands extends ParseCartridges
{
    private $_page; //DomDocument главной страницы http://www.rashodnika.net
    private $_brand; //массив всех брендов принтеров представленных на http://www.rashodnika.net

    public function __construct()
    {
        parent::__construct();
        $this->_page = self::$_dom;


    }

    public function readInfoFromBrands()
    {
        /*
         * массив $aimElements:предоставляем данные для поиска элемента 4ой вложенности в DOM дереве
         *
         * 'div'-имя коллекции элементов с которых начинаем поиск,
         * array('id'=>'sections')-значение атрибута id(можно указывать только этот атрибут,другие не поддерживаются) элемента div с которого начинаем поиск
         * ul-коллекция элементов второй вложенности
         * li-коллекция элементов третьей вложенности
         * a-коллекция элементов четвертой вложенности,ака. 'тот элемент ради которого это все затевалось'
         * */
        $aimElements = array('div', array('id' => 'sections'), 'ul', 'li', 'a');
        $this->_brand = CartridgesMemory::getElementWithId($this->_page, $aimElements);
        return $this->_brand; //получил [brand]=>'link'


    }

    public function parseDataToXMLFiles()
    {
    }

    public function getResult()
    {
    }

    public function linkLoader($links)
    {
        foreach ($links as $link) {
            parent::__construct($link);
            $this->getDomResult(self::$_dom);
        }

    }

    public function getDomResult($dom)
    {

    }
}



