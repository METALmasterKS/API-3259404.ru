<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 */
namespace PriceParse\FileModel;

use Application\Model\ConfigModel;
use PriceParse\Controller\AdministratorController;
use PriceParse\Controller\AdministratorKomusController;
use PriceParse\CronModel\CronModel;
use PriceParse\DomModel\SourceFabricaDomModel;
use PriceParse\Model\TableModel;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp as SmtpTransport;
use Zend\Mail\Transport\SmtpOptions;
use Zend\Mime\Mime;
use Zend\Mime\Part;



/**
 * Class FileModel
 * читаем данные из загруженного Excel файла(LOAD_FILE),переводим эти данные в массив PHP,
 * через метод letsParseWorkingLinks по полученному массиву,парсим ресурсы,
 * записываем результат в файл TXT_OLD_FILE,после того как все ресурсы спарсили,заменяем TXT_OLD_FILE
 * на файл XLS_NEW_FILE
 * @package PriceParse\FileModel
 */
class FileModel{
    private $_divideParams=array();//св-во содержащее строки
    public $_workingLinks=array();//строки из $this->_divideParams преобазованы в работающие ссылки

    static  $tableModel;

    public $status;

    CONST LOAD_FILE="./data/price_data_test.xls";//путь и имя для загруженного файла xls
    CONST TXT_OLD_FILE="./data/result.txt";//путь и имя для файла в который  пишутся спарсенные данные
    CONST XLS_NEW_FILE="./data/result.xls";//путь и имя для файла который подменяет файл TXT_OLD_FILE
    CONST LOG_FILE_NAME="./data/logError.log";//путь и имя для log файла

    CONST LOAD_TXT_FILE="./data/price_data_test.txt";//путь и имя для загруженного TXT файла

    CONST MOSCOW_REGION= "area=77;region=0";//значение кук под московский регион КОМУС


    CONST LOAD_TXT_FILE_KOMUS="./data/price_data_test_komus.txt";//путь и имя для загруженного  TXT файла КОМУСА
    CONST TXT_OLD_FILE_KOMUS="./data/result_komus.txt";//путь и имя для файла в который  пишутся спарсенные данные

    /**
     * получаем PHP массив данных из excel файла,подготавливаем массив для метода  letsParseWorkingLinks
     */
    public function __construct($adminModelInstance,$fluence=''){

        //$this->_tableModel=new TableModel($adminModelInstance);
        self::$tableModel=new TableModel($adminModelInstance);
        //$getArrayDataFromExcel=$this->_tableModel->getDataFromDBForPriceParseWithLimitWhereStatusOff('parse_competitors_prices');
       // $getArrayDataFromExcel=$this->readExcelData(self::LOAD_FILE);//получли данные из excel файла и преобразовали их в массив PHP
//        $getArrayDataFromExcel=TableModel::getDataFromDBForPriceParseWithLimitWhereStatusOff($adminModelInstance,'parse_competitors_prices');
        if(KomusFileModel::$komusSwitcher=='on'){
            $getArrayDataFromExcel=self::$tableModel->getDataFromDBForPriceParseWithLimitWhereStatusOff('parse_komus_diff_prices',array('product_1c_code_3259404','articul_product_komus'));
            if(!empty($getArrayDataFromExcel)){

                $this->_divideParams["http://www.komus.ru/product/(param)"]=array();

                foreach($getArrayDataFromExcel as $firstEntry){
                    foreach($firstEntry as  $secondEntry){
                        $this->makerKomusDivideParams('1705',$secondEntry,"http://www.komus.ru/product/(param)");//определяем $this->_divideParams версия по комусу
                    }

                }


              //  $this->parceArrayDataFromExcel($getArrayDataFromExcel);//определяем $this->_divideParams
                $this->getArrayWithWorkingLinks();//определили $this->_workingLinks
//            var_dump($this->_workingLinks);
                $this->letsParseWorkingLinks();
            }
            else{
                $this->status='complete';
            }
        }
        else{

            if(CronModel::$cronAllSwitcher=='on'){
                $getArrayDataFromExcel=self::$tableModel->getDataFromDBForPriceParseWithLimitWhereStatusOff('parse_competitors_prices_cron');
            }
            else{
                $getArrayDataFromExcel=self::$tableModel->getDataFromDBForPriceParseWithLimitWhereStatusOff();
            }
            if(!empty($getArrayDataFromExcel)){
                $this->parceArrayDataFromExcel($getArrayDataFromExcel);//определяем $this->_divideParams
                $this->getArrayWithWorkingLinks();//определили $this->_workingLinks
//            var_dump($this->_workingLinks);
                $this->letsParseWorkingLinks();
            }
            else{
                $this->status='complete';
            }
        }


    }

    /**
     * из чернового  $this->_divideParams,не пригодного для дальнейшего чтения в парсинге,создает  $this->_workingLinks,который
     * пригоден.
     */
    private function getArrayWithWorkingLinks(){
        //создание двумерного массива в котором будут лежать строки запроса с параметрами(работающие ссылки),ключами будут домашнии страницы ресурсов
        foreach($this->_divideParams as $dirtyLink=>$arrayParams){
            $slashEntryFirst=strpos($dirtyLink,'/');//нашел позицию первого вхождения слэша в ключе-ссылке
            $startHomeLink=$slashEntryFirst+2;//нашел позицию первого вхождения символа после второго слэша  в ключе-ссылке
            $endHomeLink=strpos($dirtyLink,'/',$startHomeLink);//нашел позицию третьего вхождения слэша в ключе-ссылке
           $newArrayForWorkingLinks=array();
            foreach($arrayParams as $key=>$val){
                //todo это не хорошо делать во втором цикле,попробуй в первый перенести или придумай еще что-то
                if($dirtyLink=='http://kshop.ru/search.show?s=%D0%9A(param)'){
                    $key=preg_replace('/[^0-9]/', '',$key);
                }
                $newArrayForWorkingLinks[str_replace('(param)',$key,$dirtyLink)]=$val;
            }
            $this->_workingLinks[substr($dirtyLink,$startHomeLink,$endHomeLink-$startHomeLink)]=$newArrayForWorkingLinks;
        }
    }

    /**
     * в цикле,значения массива св-ва  $this->_workingLinks передаем в качестве аргумента конструктору класса SourceFabricaDomModel,
     *  этот класс по этим данным парсит ресурсы
     */
    public function letsParseWorkingLinks()
    {

//        $header = "Код товара по 1c" . "\t" . "Код конкурента" . "\t" . "Артикул конкурента" . "\t" . "Цена продажи" . "\t" . "Описание" . "\n";
//		$header=iconv("utf-8", "windows-1251", $header);
//        SourceFabricaDomModel::spitHeaderToFile($header);
        foreach ($this->_workingLinks as $homeLink => $workingLink) {
            if (!empty($workingLink)) {
                new SourceFabricaDomModel($homeLink, $workingLink);
            }
        }
        //создаем копию Result.txt переименовываем её в Result.xls,удаляем Result.txt
        //$this->managerFilesConverter();
        //self::senderMailer();//отправляем результат на почту администратору
        //self::deleteAllCreatedData();//удаляем все файлы,удаляем сессию
        return;
    }

    /**
     * метод отправления письма с вложением
     */
    public static function senderMailer($fileName=self::TXT_OLD_FILE,$fileNameToEmail='parse_data_'){
        if($fileNameToEmail=='parse_data_komus'){
            $emailAddress=AdministratorKomusController::checkSessionCatchLogKomus();
        }
        else{
            $emailAddress=AdministratorController::checkSessionCatchLog();
        }


        $emailMessage=new Message();

        $bodyPart=new \Zend\Mime\Message();
        $attachment=new Part(fopen($fileName,'r'));
       // $attachment->type = 'application/vnd.ms-excel';
        $attachment->type = 'text/plain';
        $attachment->filename=$fileNameToEmail.date('d-m-Y_H:i:s', time()).'.txt';
        $attachment->disposition=Mime::DISPOSITION_ATTACHMENT;
        $bodyPart->setParts(array($attachment));

        $emailMessage->addFrom("localhost@localhost.com", "Ренат Османов")
            ->addTo($emailAddress)//статический метод в аргументе возвращает значение  сессии с email администратора
            ->setSubject("Парсинг из TXT");
        $emailMessage->setBody($bodyPart);

        $cm=ConfigModel::getConfigData('./config/autoload/config.json');
        $transportEmailMessage=new SmtpTransport();
        $options=new SmtpOptions(array(
                                 'name' => 'localhost',
                                 'host' => 'smtp.gmail.com',
                                 'port'=> 587,
                                 'connection_class' => 'login',
                                 'connection_config' => array(
                                     'username' => $cm['postman']['username'],
                                     'password' => $cm['postman']['password'],
                                     'ssl'=> 'tls',
                                 ),));
        $transportEmailMessage->setOptions($options);
        $transportEmailMessage->send($emailMessage);

    }


    /**
     * в св-ве $this->_divideParams создаем массив соответсвий,по кодам из массива
     * пришедшего из Excel файла раскидываем значения этого массива в ключи массива $this->_divideParams
     *
     * @param $arrayDataFromExcel
     * массив который пришел из метода FileModel::readExcelData
     *
     * @throws \Exception
     */

    public function parceArrayDataFromExcel($arrayDataFromExcel){
        //удаляем первую ячейку массива,т.к там может быть кракозябра с названиями столбцов из excel
       // array_shift($arrayDataFromExcel);
        //в массив $this->_divideParams в ключи пишем URL в значение пустой массив
        $this->_divideParams["http://www.zhivojoffice.ru/search/index?search=(param)"]=array();
        $this->_divideParams["http://www.globaltrading.ru/search.php?text=(param)"]=array();
        $this->_divideParams["http://kshop.ru/search.show?s=%D0%9A(param)"]=array();
        $this->_divideParams["http://www.shop.kostyor.ru/index.php?view=(param)&c=detail"]=array();
        $this->_divideParams["http://www.komus.ru/product/(param)"]=array();
        $this->_divideParams["http://www.officemag.ru/search/?q=(param)"]=array();
        $this->_divideParams["http://www.ofisshop.ru/search/?q=(param)&s=%D0%98%D1%81%D0%BA%D0%B0%D1%82%D1%8C"]=array();


       //заполняем массив $this->_divideParams[URL][] значениями из массива полученного в методе readExcelData()
        foreach($arrayDataFromExcel as $firstEntry){
            foreach($firstEntry as  $secondEntry){
                $code=trim((string)$secondEntry['competitor_code']);
                switch($code){
                    case("17400"):$this->makerDivideParams($code,$secondEntry,"http://www.zhivojoffice.ru/search/index?search=(param)");break;
                    case("17402"): $this->makerDivideParams($code,$secondEntry,"http://www.globaltrading.ru/search.php?text=(param)");break;
                    case("17403"): $this->makerDivideParams($code,$secondEntry,"http://www.ofisshop.ru/search/?q=(param)&s=%D0%98%D1%81%D0%BA%D0%B0%D1%82%D1%8C");break;
                    case("17404"):$this->makerDivideParams($code,$secondEntry,"http://kshop.ru/search.show?s=%D0%9A(param)");break;
                    case("17405"):$this->makerDivideParams($code,$secondEntry,"http://www.komus.ru/product/(param)");break;
                    case("17406"):$this->makerDivideParams($code,$secondEntry,"http://www.officemag.ru/search/?q=(param)");break;
                    case("17646"):$this->makerDivideParams($code,$secondEntry,"http://www.shop.kostyor.ru/index.php?view=(param)&c=detail");break;
                    case("1720"):  break;
                    default:throw new \Exception('Записи  в файле не существует');
                }
               // echo $secondEntry['competitor_code']."<br/>";
//                var_dump($secondEntry);
            }
        }
    }
    /**
     * создатель-рулевой массива $this->_divideParams
     *
     * @param $code
     * код с-йта
     * @param $secondEntry
     * массив содержащий данные для $code
     * @param $stringLink
     * ключ типа http://www.zhiv...
     */
    public function makerDivideParams($code,$secondEntry,$stringLink){
        $this->_divideParams[$stringLink][$secondEntry['articul_product']][]=$secondEntry['product_1c_code'];//1C
        $this->_divideParams[$stringLink][$secondEntry['articul_product']][]=$code;//код конкурента
    }
    // создатель-рулевой массива $this->_divideParams для парсинга разниц цен по комусу
    public function  makerKomusDivideParams($code,$secondEntry,$stringLink){
        $this->_divideParams[$stringLink][$secondEntry['articul_product_komus']][]=$secondEntry['product_1c_code_3259404'];//1C
        $this->_divideParams[$stringLink][$secondEntry['articul_product_komus']][]=$code;//код конкурента
    }



    /**
     * заменяем self::TXT_OLD_FILE на self::XLS_NEW_FILE
     */
    public static function managerFilesConverter(){
        if(file_exists(self::TXT_OLD_FILE)){
            if (!copy(self::TXT_OLD_FILE, self::XLS_NEW_FILE)) {
                 $logger=new Logger();
                 $writer=new Stream(self::LOG_FILE_NAME);
                 $logger->addWriter($writer);
                 $logger->info('Не удалось преобразовать файл из txt в xls');
            }
            else{
                unlink(self::TXT_OLD_FILE);
            }
        }
    }

    /**
     * Метод читает загруженный Excel файл,полученыые данные преобразует в массив PHP
     *
     * @param $inputFileName
     * имя загруженного excel файла (LOAD_FILE)
     *
     * @return array
     * массив полученный из данных Excel файла
     */
    public function readExcelData($inputFileName){
        try {
            $inputFileType = \PHPExcel_IOFactory::identify($inputFileName);
            $objReader = \PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($inputFileName);
        } catch(Exception $e) {
            die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
        }

        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        //  Loop through each row of the worksheet in turn
        $rowData=array();
        for ($row = 1; $row <= $highestRow; $row++){
            //  Read a row of data into an array
            $rowData[] = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
        }
       return $rowData;
    }

    public static function readDataFromDB(){

    }




    /**
     * Метод читает через self::txtDataToTwoDimensionalArray загруженный Txt файл,
     * полученыые данные преобразует в массив трехмерный массив PHP
     *
     * @param string    $inputFileName имя txt файла
     *
     * @return array
     */
    public function readTxtData($inputFileName){
        $commonResultArray=self::txtDataToTwoDimensionalArray($inputFileName);
        $arrayToResult=array();
        foreach($commonResultArray as $block){
            $arrayToResult[]=array($block);
        }
        return $arrayToResult;
    }

    /**
     * Метод читает загруженный Txt файл,преобразует в массив PHP
     *
     * @param string    $inputFileName имя txt файла
     *
     * @return array                   двумерный массив из txt файла
     */
    public static function txtDataToTwoDimensionalArray($inputFileName){
        $row = 1;
        $arrayFromFile=array();
        if (($handle = fopen($inputFileName, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 0, "\t")) !== FALSE) {
                $row++;
                for ($c=0; $c < 4; $c++) {
                    $arrayFromFile[]=$data[$c];
                }
            }
            fclose($handle);
        }
       return array_chunk($arrayFromFile, 4);
    }





    /**
     * удаляем все загруженные,созданные файлы,удаляем сиссию и куки
     */
    public static function deleteAllCreatedData($load=self::LOAD_TXT_FILE,$result=self::TXT_OLD_FILE,$sessName='user'){
//        if(file_exists(self::XLS_NEW_FILE)){
           // unlink(self::XLS_NEW_FILE);
//
//        }
            unlink($load);
            if($load==CronModel::CRON_LOAD_TXT_FILE ||$load==CronModel::CRON_LOAD_TXT_FILE_KOMUS) return;
            if(file_exists($result)){
                unlink($result);
            }

            $params = session_get_cookie_params();
            setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
            unset($_SESSION[$sessName]);


    }


    public static function deleteAllCreatedDataForButton($load=self::LOAD_TXT_FILE,$result=self::TXT_OLD_FILE){
//        if(file_exists(self::XLS_NEW_FILE)){
        // unlink(self::XLS_NEW_FILE);
//
//        }
        if(file_exists($result)){
            unlink($result);
        }
        unlink($load);

    }

    /**
     * поиск в директории по названию файла
     *
     * @param string    $findStartFileName  начало имяни файла который ищем
     * @param array $arrayFromDir   массив с именами файлов которые находятся в директории поиска
     *
     * @return string   полное имя файла которое ищем в директории
     */
    public static function getFileLikeStartNameFromScanDir($findStartFileName,array $arrayFromDir){
        foreach($arrayFromDir as $dirOrFile){
            $pos = stripos($dirOrFile,$findStartFileName);
            if($pos!==false){
                return $dirOrFile;
            }
        }
    }

    public static  function garbageAllForCron(array $files){
        foreach($files as $file){
           if(file_exists($file)) unlink($file);
        }
    }




    /**
     * в случае ошибки подмены с self::TXT_OLD_FILE на self::XLS_NEW_FILE,
     * пишем сообщение в лог-файл
     */
    public static function LoggerFileTransformErrorCaseCreator(){
        $logger=new Logger();
        $writer=new Stream(self::LOG_FILE_NAME);
        $logger->addWriter($writer);
        $logger->info('Не удалось преобразовать файл из txt в xls');
    }

    /**
     * Метод для преобразования в корректный UTF-8,нужен для отображения кирилических символов с tech.komus
     *
     * @param $str
     *
     * @return string
     */
    public static function to_norm_utf8($str){
        $table = array(
            "\xC3\x90\xC2\xA0"=>"\xD0\xA0", "\xC3\x90"=>"\xD0", "\xC3\x91"=>"\xD1", "\xC3\x92"=>"\xD2", "\xC2\x91"=>"\xD1",
            "\xC2\x90"=>"\x90", "\xC2\x91"=>"\x91", "\xC2\x92"=>"\x92", "\xC2\x93"=>"\x93", "\xC2\x94"=>"\x94",
            "\xC2\x95"=>"\x95", "\xC2\x81"=>"\x81", "\xC2\x96"=>"\x96", "\xC2\x97"=>"\x97", "\xC2\x98"=>"\x98",
            "\xC2\x99"=>"\x99", "\xC2\x9A"=>"\x9A", "\xC2\x9B"=>"\x9B", "\xC2\x9C"=>"\x9C", "\xC2\x9D"=>"\x9D",
            "\xC2\x9E"=>"\x9E", "\xC2\x9F"=>"\x9F", "\xC2\xA1"=>"\xA1", "\xC2\xA2"=>"\xA2", "\xC2\xA3"=>"\xA3",
            "\xC2\xA4"=>"\xA4", "\xC2\xA5"=>"\xA5", "\xC2\xA6"=>"\xA6", "\xC2\xA7"=>"\xA7", "\xC2\xA8"=>"\xA8",
            "\xC2\xA9"=>"\xA9", "\xC2\xAC"=>"\xAC", "\xC2\xAA"=>"\xAA", "\xC2\xAB"=>"\xAB", "\xC2\xAD"=>"\xAD",
            "\xC2\xAE"=>"\xAE", "\xC2\xAF"=>"\xAF", "\xC2\x86"=>"\x86", "\xC2\x87"=>"\x87", "\xC2\x84"=>"\x84",
            "\xC2\x90"=>"\x90", "\xC2\xB0"=>"\xB0", "\xC2\xB1"=>"\xB1", "\xC2\xB2"=>"\xB2", "\xC2\xB3"=>"\xB3",
            "\xC2\xB4"=>"\xB4", "\xC2\xB5"=>"\xB5", "\xC2\x91"=>"\x91", "\xC2\xB6"=>"\xB6", "\xC2\xB7"=>"\xB7",
            "\xC2\xB8"=>"\xB8", "\xC2\xB9"=>"\xB9", "\xC2\xBA"=>"\xBA", "\xC2\xBB"=>"\xBB", "\xC2\xBC"=>"\xBC",
            "\xC2\xBD"=>"\xBD", "\xC2\xBE"=>"\xBE", "\xC2\xBF"=>"\xBF", "\xC2\x80"=>"\x80", "\xC2\x81"=>"\x81",
            "\xC2\x82"=>"\x82", "\xC2\x83"=>"\x83", "\xC2\x84"=>"\x84", "\xC2\x85"=>"\x85", "\xC2\x86"=>"\x86",
            "\xC2\x87"=>"\x87", "\xC2\x88"=>"\x88", "\xC2\x89"=>"\x89", "\xC2\x8C"=>"\x8C", "\xC2\x8A"=>"\x8A",
            "\xC2\x8B"=>"\x8B", "\xC2\x8D"=>"\x8D", "\xC2\x8E"=>"\x8E", "\xC2\x8F"=>"\x8F", "\xC2\x96"=>"\x96",
            "\xC2\x97"=>"\x97", "\xC2\x94"=>"\x94", "\xC2\x91"=>"\x91", "\xC2\xA0"=>"\x20");
        $str = strtr($str, $table);//все &nbsp; станут пробелами -> " "
        return $str;
    }

    /**
     * Получаем контент комуса под константу региона
     *
     * @param string | $cookieString константа региона
     * @param $url
     *
     * @return mixed если ресурс не найден то вернет false
     */
    public static  function getKomusContentForUrlThroughRegionSwitcher($cookieString,$url){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_COOKIE, $cookieString);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, '1');
        $resource=curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close ($ch);
        if($info['http_code']==404) return false;
        return $resource;
    }


    public static function checkIssetCompetitorsFile($fileName){
        if(file_exists($fileName)){
            return true;
        }
        return false;
    }





}