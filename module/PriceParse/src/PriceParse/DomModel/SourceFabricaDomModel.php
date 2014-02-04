<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 */
namespace PriceParse\DomModel;

use PriceParse\CronModel\CronModel;
use PriceParse\FileModel\FileModel;
use PriceParse\FileModel\KomusFileModel;
use PriceParse\Model\TableModel;
use Zend\Dom\Query;

/**
 * Class SourceFabricaDomModel
 * получает данные в конструктор от св-ва FileModel::_workingLinks,
 * в методе FileModel::letsParseWorkingLinks(),по пришедшим данным осуществляется парсинг
 * через конструктор SourceFabricaDomModel
 *
 * @package PriceParse\DomModel
 */
class SourceFabricaDomModel{
    private $_dom;

    /**
     * @param $homeLink
     *
     * @param $workingLink
     * содержит в ключах ссылку с шаблоном,в значенииях артикул товара,
     * подставляя артикул под шаблон получаем рабочую ссылку
     *
     * @throws \Exception
     */
    function __construct($homeLink,$workingLink){
        switch($homeLink){
            case('www.komus.ru'):

                foreach($workingLink as $art=>$codes){

                    $this->komusParse($art,$codes);

                }
                break;
            case('www.zhivojoffice.ru'):
                foreach($workingLink as $art=>$codes){
                    $this->zhivojofficeParse($art,$codes);

                }break;
            case('www.globaltrading.ru'):
                foreach($workingLink as $art=>$codes){
                    $this->globaltradingParse($art,$codes);

                }break;
            case('kshop.ru'):
                foreach($workingLink as $art=>$codes){
                    $this->kshopParse($art,$codes);


                }break;
            case('www.shop.kostyor.ru'):
                foreach($workingLink as $art=>$codes){
                    $this->shopkostyorParse($art,$codes);

                }break;
            case('www.officemag.ru'):

                foreach($workingLink as $art=>$codes){
                    $this->officemagParse($art,$codes);

                }break;
            case('www.ofisshop.ru'):

                foreach($workingLink as $art=>$codes){
                    $this->ofisshopParse($art,$codes);

                }break;
            default:throw new \Exception('Записи '.$homeLink.' в файле не существует');

        }
    }
    /**
     * 17400,www.zhivojoffice.ru
     *
     * @param $link
     * ссылка на ресурс
     *
     * @param $codes
     * артикул товара
     */
    public  function zhivojofficeParse($link,$codes){
 /*       $getContentUrl=$this->getContentForUrl($link);
        if($getContentUrl){
            $this->_dom=new Query($getContentUrl);
            $nodesTd=$this->_dom->execute('#search_res > table > tr > td .grn11 > table > tr > td');
            $count=count($nodesTd);
            if($count){

                //артикул
                //$articul=trim(preg_replace('/[^0-9]/', '', $nodesTd[1]->getElementsByTagName("span")->item(0)->textContent));
                //наименование
                $label=$nodesTd[2]->getElementsByTagName("a")->item(0)->textContent;
                //цена
                $price=substr(trim(preg_replace('/[^0-9\.]/', '', $nodesTd[3]->getElementsByTagName("span")->item(0)->textContent)),0,-1);
				$price=chr(32).$price;
            }
            else{

               // $articul=substr(trim(strrchr($link, "=")), 1);
                $label="-";
				
                $price="не найдено на сайте";
				
            }



        }

        //для случая когда ресурс не доступен и возвращается 404 Not Found
        else{
            //$articul=substr(trim(strrchr($link, "=")), 1);
            $price="Ресурс для парсинга не доступен";
            $label="-";
        }

//		 $price=iconv("utf-8", "windows-1251", $price);
//		 $label=iconv("utf-8", "windows-1251", $label);
        $articul=substr(trim(strrchr($link, "=")), 1);
        $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);
*/
        $getContentUrl=$this->getContentForUrl($link);
        if($getContentUrl){
            $this->_dom=new Query($getContentUrl);


            //tests
            // $this->test=count($this->_dom->execute('#search_res > table > tr > td .grn11'));
            $nodesTdTest=$this->_dom->execute('#search_res > table > tr > td .grn11');

            $countTest=count($nodesTdTest);
            if($countTest >= 1){
                $needleArticul=substr(trim(strrchr($link, "=")), 1);
                foreach($nodesTdTest as $nodeTest){
                    $tableTest=$nodeTest->getElementsByTagName("table");
                    $trTest=$tableTest->item(0)->getElementsByTagName("tr");
                    $tdTest=$trTest->item(0)->getElementsByTagName("td");
                    $articulForThisNodeTestDirty=$tdTest->item(1)->getElementsByTagName("span")->item(0)->textContent;
                    $articulForThisNodeTestClear=trim(preg_replace('/[^0-9]/', '', $articulForThisNodeTestDirty));
                    if($needleArticul===$articulForThisNodeTestClear){
                        $label=$tdTest->item(2)->getElementsByTagName("a")->item(0)->textContent;
                        $price=substr(trim(preg_replace('/[^0-9\.]/', '', $tdTest->item(3)->getElementsByTagName("span")->item(0)->textContent)),0,-1);
                        $price=chr(32).$price;
                        $articul=$articulForThisNodeTestClear;
                        break;
                    }
                    //else{
//                        $articul="артикулы не равны";
//                        $price="неизвестно";
//                        $label="неизвестно";
                    //}
                }
                if(!isset($articul) || !isset($price)){
                    $articul=$needleArticul;
                    $price="не найдено на сайте";
                    $label="-";
                }

            }

            else{
                $label="-";
                $articul=substr(trim(strrchr($link, "=")), 1);
                $price="не найдено на сайте";
            }
        }
        else{
            $articul=substr(trim(strrchr($link, "=")), 1);
            $price="Ресурс для парсинга не доступен";
            $label="-";
        }

        $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);
    }


    /**
     * 17402,www.globaltrading.ru
     *
     * @param $link
     * ссылка на ресурс
     *
     * @param $codes
     * артикул товара
     */
    public function globaltradingParse($link,$codes){
        $getContentUrl=$this->getContentForUrl($link);
        if($getContentUrl){
            $this->_dom=new Query($getContentUrl);
            $nodesTable=$this->_dom->execute('.price_list_item > table');

            $count=count($nodesTable);


            if($count){
                //элементы tr и td(для получения артикула и цены)
                $tr=$nodesTable[1]->getElementsByTagName("tr")->item(0);
                $td=$tr->getElementsByTagName("td");

                //для получения артикула
               // $spanArticul=$td->item(0)->getElementsByTagName("span")->item(0);
                //артикул
               // $articul=$spanArticul->textContent;

                //для получения цены
                $spanPrice=$td->item(1)->getElementsByTagName("span")->item(0);
                //цена
                $price=str_replace(',','',$spanPrice->textContent);
				$price=chr(32).$price;

                //элемент h1(для получения наименования)
                $header1=$nodesTable[0]->getElementsByTagName("h1")->item(0);
                //наименование
                $label=$header1->getElementsByTagName("a")->item(0)->textContent;
				

            }
            else{
                //$articul=substr(trim(strrchr($link, "=")), 1);
                $label="-";
                $price="не найдено на сайте";
				
            }

        }
        //для случая когда ресурс не доступен и возвращается 404 Not Found
        else{
            //$articul=substr(trim(strrchr($link, "=")), 1);
            $price="Ресурс для парсинга не доступен";
            $label="-";
        }
        /**
         * $codes[0]-код 1С
         * $codes[1]-код конкурента
         */
//		 $price=iconv("utf-8", "windows-1251", $price);
//		 $label=iconv("utf-8", "windows-1251", $label);
        $articul=substr(trim(strrchr($link, "=")), 1);
        $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);
    }

    /**
     * 17404,http://kshop.ru-без www,с www не открывает товар,буква К в параметрах товара на кириллице у них
     *
     * @param $link
     * ссылка на ресурс
     *
     * @param $codes
     * артикул товара
     */
    public function kshopParse($link,$codes){
        $getContentUrl=$this->getContentForUrl($link);
        if($getContentUrl){
            $this->_dom=new Query($getContentUrl);
            $nodeDiv=$this->_dom->execute('.cat_block');

            $count=count($nodeDiv);
            if($count){

                $p=$nodeDiv[0]->getElementsByTagName("p")->item(0);
                $brand=$p->getElementsByTagName("b")->item(0)->textContent;
                $description=$nodeDiv[0]->getElementsByTagName("a")->item(1)->textContent;
                $label=$brand." ".$description;

                //$articul=$nodeDiv[0]->getElementsByTagName("div")->item(0)->textContent;

                $divForPrice=$nodeDiv[0]->getElementsByTagName("div")->item(1);
                $price=str_replace(" р.","",$divForPrice->getElementsByTagName("span")->item(0)->textContent);
				$price=chr(32).$price;
            }
            else{
//                $articul=str_replace('%D0%9A','=',$link);
//                $articul="K".substr(trim(strrchr($articul, "=")), 1);
                $label='-';
                $price='не найдено на сайте';
				
            }
        }
        //для случая когда ресурс не доступен и возвращается 404 Not Found
        else{
//            $articul=str_replace('%D0%9A','=',$link);
//            $articul="K".substr(trim(strrchr($articul, "=")), 1);
            $price="Ресурс для парсинга не доступен";
            $label='-';
        }
        /**
         * $codes[0]-код 1С
         * $codes[1]-код конкурента
         */
//		 $articul=iconv("utf-8", "windows-1251", $articul);
//		 $price=iconv("utf-8", "windows-1251", $price);
//		 $label=iconv("utf-8", "windows-1251", $label);
        $articul=str_replace('%D0%9A','=',$link);
        $articul="K".substr(trim(strrchr($articul, "=")), 1);
        $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);
    }

    /**
     * 17646,http://www.shop.kostyor.ru
     *
     * @param $link
     * ссылка на ресурс
     *
     * @param $codes
     * артикул товара
     */
    public function shopkostyorParse($link,$codes){
        $getContentUrl=$this->getContentForUrl($link);
        if($getContentUrl){
            $this->_dom=new Query($getContentUrl);
            $nodeTable=$this->_dom->execute('#centrecontent > #rt .center > table');
            $tr=$nodeTable[0]->getElementsByTagName("tr")->item(0);

//            //для получения артикула
//            $tdArticul=$tr->getElementsByTagName("td")->item(0);
//            //получаем артикул
//            foreach($tdArticul->childNodes as $item) {
//                if($item->nodeName=='span'){
//                    $articul=$item->textContent;
//                    break;
//                }
//            }

            //для получения наименования и цены
            $tdContent=$tr->getElementsByTagName("td")->item(1);

            //получаем наименование
            $label=$tdContent->getElementsByTagName("span")->item(0)->textContent;

            //для получения цены
            $formPrice=$tdContent->getElementsByTagName("form")->item(0);

            $counterTable=0;
            foreach($formPrice->childNodes as $itemPrice) {
                if($itemPrice->nodeName=='table'){
                    $counterTable++;
                }
            }


            $tablePrice=$formPrice->getElementsByTagName("table")->item($counterTable-1);
            $trPrice=$tablePrice->getElementsByTagName("tr")->item(0);
            $counterTdPrice=0;
            foreach($trPrice->childNodes as $itemTdPrice) {
                if($itemTdPrice->nodeName=='td'){
                    $counterTdPrice++;
                }
            }
            //получаем цену
            if($counterTdPrice==3){
                $tdPrice=$trPrice->getElementsByTagName("td")->item(0);
                $price=$tdPrice->getElementsByTagName("div")->item(1)->textContent;
				$price=chr(32).$price;
            }
            elseif($counterTdPrice==4){
                $tdPrice=$trPrice->getElementsByTagName("td")->item(1);
                $price=$tdPrice->getElementsByTagName("div")->item(0)->textContent;
				$price=chr(32).$price;
            }
            else{
                $price="Ошибка в парсинге,конкуренты изменили свою структуру сайта";
				
            }

            if($counterTdPrice==3 || $counterTdPrice==4){
                $price=str_replace(" руб.","",$price);
				
            }


        }

        else{
           // $articul=substr(strstr(str_replace("&c=detail","",$link),"="),1);
            $price="Ресурс для парсинга не доступен";
            $label='-';
        }
        /**
         * $codes[0]-код 1С
         * $codes[1]-код конкурента
         */
//		$price=iconv("utf-8", "windows-1251", $price);
//		$label=iconv("utf-8", "windows-1251", $label);
        $articul=substr(strstr(str_replace("&c=detail","",$link),"="),1);
        $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);
    }

    /**
     * 17405,http://www.komus.ru
     *
     * @param $link
     * ссылка на ресурс
     *
     * @param $codes
     * артикул товара
     */
     public function komusParse($link,$codes){


            //$getContentUrl=$this->getContentForUrl($link);
            $getContentUrl=FileModel::getKomusContentForUrlThroughRegionSwitcher(FileModel::MOSCOW_REGION,$link);
            if($getContentUrl){
                $this->_dom=new Query($getContentUrl);
                $checkerStream=$this->_dom->execute('.t14_text_block');
                if(count($checkerStream)){
                    $nodeLabel=$this->_dom->execute('.t14_text_block > h1');
                    $nodeArticul=$this->_dom->execute('.t14_text_block  .t14_articul_sklad_info .t14_articul_info');
                    //когда на странице присутсвует discount цена
                    // $nodePrice=$this->_dom->execute('.t14_text_block .t14_price_info .t14_price_info_low .t14_price_low');
                    //обычная страница
                    //if(!count($nodePrice)){
                    $nodePrice=$this->_dom->execute('.t14_text_block .t14_price_info .t14_price_good');
                    //}

                    //$nodeChecker будет true если товар есть на складе
                    $nodeChecker=count($this->_dom->execute('.t14_sklad_info_red'))? false:true;

                    $label=$nodeLabel[0]->textContent;
                   // $articul=trim(preg_replace('/[^0-9]/', '', $nodeArticul[0]->textContent));


                    if($nodeChecker){

                        $price=trim(str_replace(" ","",str_replace(" руб.","",$nodePrice[0]->textContent)));
                        $price=str_replace(",",".",$price);
                        $price=chr(32).$price;
                    }
                    else{
                       // $price='Товар отсутствует в продаже';
                        $messageResult=$this->_dom->execute('.t14_sklad_info_red');
                        $price=$messageResult[0]->textContent;
                        if($price=='Товар отсутствует в продаже'){
                            $price='нет в наличии';
                        }
                        if($price=='Продажи товара прекращены'){
                            $price='товар выведен';
                        }
                    }
                }
                //проверка  на ресурсе tech.komus.ru
                else{
                    $checkerSpareStream=$this->_dom->execute('.product-card--info');
                    if(count($checkerSpareStream)){

                        //описание
                        $nodeLbl=$this->_dom->execute('.header-gray--frame-left-inside > h1');
                        //$label=FileModel::to_norm_utf8($nodeLbl[0]->textContent);
                        $label="FLAG-TECH-KOMUS-".$nodeLbl[0]->textContent;




                        //цена
                        $nodePrice=$this->_dom->execute('.product-card--price-now > span');
                        $price=$nodePrice[0]->textContent;
                        $price=chr(32).$price;

                        //артикул
                        $nodeArticul=$this->_dom->execute('.product-card--articul');
                        //$articul=trim(preg_replace('/[^0-9]/', '', $nodeArticul[0]->textContent));


                    }
                    else{
                        $label='-';
                        $price="Ресурс для парсинга не доступен";
                    }

                }

            }
            //для случая когда ресурс не доступен и возвращается 404 Not Found
            else{
               // $articul=substr(strrchr($link,'/'),1);
                $label='-';
                $price="Ресурс для парсинга не доступен";
				


            }
        /**
         * $codes[0]-код 1С
         * $codes[1]-код конкурента
         */
//		 $price=iconv("utf-8", "windows-1251", $price);
//		 $label=iconv("utf-8", "windows-1251", $label);
         $articul=substr(strrchr($link,'/'),1);
         $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);




    }
    /**
     * 17406 www.officemag.ru
     *
     * @param $link
     * ссылка на ресурс
     *
     * @param $codes
     * артикул товара
     */
    public function officemagParse($link,$codes){

        $getContentUrl=$this->getContentForUrl($link);
        if($getContentUrl){
            $this->_dom=new Query($getContentUrl);
            $this->_dom->setEncoding('windows-1251');
            $nodeChecker=$this->_dom->execute('.itemInfo .itemInfoDetails');
            $nodeLabel=$this->_dom->execute('.itemInfo .itemInfoDetails > h1');
            //$nodeArticul=$this->_dom->execute('.itemInfo .itemInfoDetails .specialBar .code');
            $nodePrice=$this->_dom->execute('.itemInfo .itemInfoDetails .order .price > span');

            if(count($nodeChecker)){
                $label= $nodeLabel[0]->textContent;
               // $articul= trim(preg_replace('/[^0-9]/', '',$nodeArticul[0]->textContent));
                $price=trim(preg_replace('/[^0-9\.]/', '',$nodePrice[0]->textContent));
				 $price=chr(32).$price;
            }
            else{
				$label= '-';
                //$articul=substr(strrchr($link,'='),1);
                $price='не найдено на сайте';
				
            }
        }
        //для случая когда ресурс не доступен и возвращается 404 Not Found
        else{
			$label= '-';
          //  $articul=substr(strrchr($link,'='),1);
            $price="Ресурс для парсинга не доступен";
			
        }
        /**
         * $codes[0]-код 1С
         * $codes[1]-код конкурента
         */
		// $label=iconv("utf-8", "windows-1251", $label); 
		//$price=iconv("utf-8", "windows-1251", $price);
        $articul=substr(strrchr($link,'='),1);
        $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);
    }

    /**
     * 17403,http://www.ofisshop.ru
     *
     * @param $link
     * ссылка на ресурс
     *
     * @param $codes
     * артикул товара
     */
    public function ofisshopParse($link,$codes){
        $getContentUrl=$this->getContentForUrl($link);
        if($getContentUrl){
            $this->_dom=new Query($getContentUrl);
                $nodeChecker=$this->_dom->execute('.card-rigth');
                if(count($nodeChecker)){
                    $nodeLabel=$this->_dom->execute('.card-rigth > h1');
                    //$nodeArticul=$this->_dom->execute('.card-rigth span[itemprop="identifier"]');
                    $nodePrice=$this->_dom->execute('.card-rigth .offer span[itemprop="price"]');
                    $label=$nodeLabel[0]->textContent;
                    //$articul=$nodeArticul[0]->textContent;
                    $price=$nodePrice[0]->textContent;
					 $price=chr(32).$price;
					
                }
                else{
                    /**
                     * в strstr получаем из $link обрезанную строку до символа &,
                     *в strrchr получаем из $link строку от последного знака '=' до конца строки,
                     * затем очищаем со всех стророн от посторонних символов,затем вырезаем всю строку справа от символа '=',и кладем в $artcul
                     */
                   // $articul=substr(trim(strrchr(strstr($link, '&', true), "=")),1);
                    $label="-";
                    $price='Товар отсутствует в продаже';
					
                }

        }
        //для случая когда ресурс не доступен и возвращается 404 Not Found
        else{
			$label="-";
            //$articul=substr(trim(strrchr(strstr($link, '&', true), "=")),1);
            $price="Ресурс для парсинга не доступен";
			
        }
        /**
         * $codes[0]-код 1С
         * $codes[1]-код конкурента
         */
//		 $price=iconv("utf-8", "windows-1251", $price);
//		 $label=iconv("utf-8", "windows-1251", $label);
        $articul=substr(trim(strrchr(strstr($link, '&', true), "=")),1);
        $this->fileDataRecorder($codes[0],$codes[1],$articul,$price,$label);
    }

    /**
     * Формирование строки для записи в файл
     *
     * @param $code1C
     * код 1С
     *
     * @param $codeConcurent
     * код сайта конкурента
     *
     * @param $articul
     * артикул товара
     *
     * @param $price
     * цена 
	 *
     * @param $label
     * описание
     */
    private function fileDataRecorder($code1C,$codeConcurent,$articul,$price,$label){
//        $resultString=$code1C."\t".$codeConcurent."\t".$articul."\t".$price."\t".$label."\n";
//        $this->spitResultFileOut($resultString);
        //array('product_1c_code'=>$string[0],'competitor_code'=>$string[1],'articul_product'=>$string[2])
//putInDbParseData(array $insert)
        if(KomusFileModel::$komusSwitcher=='on'){
            TableModel::updateInDbForParseData(array('product_1c_code_3259404'=>$code1C,'articul_product_komus'=>$articul),array('price_product_komus'=>$price,'product_description'=>$label,'status'=>'ok'),'parse_komus_diff_prices');
            return;
        }
        $tableNameForAllCronOrNot=(CronModel::$cronAllSwitcher=='on')?'parse_competitors_prices_cron':'parse_competitors_prices';
        TableModel::updateInDbForParseData(array('product_1c_code'=>$code1C,'competitor_code'=>$codeConcurent,'articul_product'=>$articul),array('price_product'=>$price,'product_description'=>$label,'status'=>'ok'),$tableNameForAllCronOrNot);
    }

    /**
     * получаем контент по $url
     *
     * @param $url
     * полная,готовая ссылка на ресурс для парсинга
     *
     * @return bool|string
     * возвращаем false в случае 404 и 502 заголовков
     */
    public  function getContentForUrl($url){

        $headers=get_headers($url);
        if(!in_array("HTTP/1.1 404 Not Found",$headers) && !in_array("HTTP/1.1 502 Bad Gateway",$headers)){
            return file_get_contents($url);
        }
        else{

            return false;
        }

    }

    /**
     * метод создает запись в файле с результатом | если файла не существует создает файл
     *
     * @param string $resultString
     * @param string $fileName  имя txt имя тхт файла куда записываем спарсенный результат
     */
    public static function spitResultFileOut($resultString,$fileName=FileModel::TXT_OLD_FILE){
        file_put_contents($fileName,$resultString,FILE_APPEND);
    }

    /**
     * запись названий столбцов в файл
     *
     * @param $header
     */
    public static function spitHeaderToFile($header){

        file_put_contents(FileModel::TXT_OLD_FILE,$header,FILE_APPEND);
    }

}



