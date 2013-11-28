<?php
/**
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 */
namespace PriceParse\FileModel;

use PriceParse\Controller\AdministratorController;
use PriceParse\DomModel\SourceFabricaDomModel;
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
    private $_workingLinks=array();//строки из $this->_divideParams преобазованы в работающие ссылки

    CONST LOAD_FILE="./data/price_data_test.xls";//путь и имя для загруженного файла xls
    CONST TXT_OLD_FILE="./data/result.txt";//путь и имя для файла в который  пишутся спарсенные данные
    CONST XLS_NEW_FILE="./data/result.xls";//путь и имя для файла который подменяет файл TXT_OLD_FILE
    CONST LOG_FILE_NAME="./data/logError.log";//путь и имя для log файла
    /**
     * получаем PHP массив данных из excel файла,подготавливаем массив для метода  letsParseWorkingLinks
     */
    public function __construct(){
        $getArrayDataFromExcel=$this->readExcelData(self::LOAD_FILE);//получли данные из excel файла и преобразовали их в массив PHP
        $this->parceArrayDataFromExcel($getArrayDataFromExcel);//определяем $this->_divideParams
        $this->getArrayWithWorkingLinks();//определили $this->_workingLinks
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

        $header = "Код товара по 1c" . "\t" . "Код конкурента" . "\t" . "Артикул конкурента" . "\t" . "Цена продажи" . "\n";
        SourceFabricaDomModel::spitHeaderToFile($header);
        foreach ($this->_workingLinks as $homeLink => $workingLink) {
            if (!empty($workingLink)) {
                new SourceFabricaDomModel($homeLink, $workingLink);
            }
        }
        //создаем копию Result.txt переименовываем её в Result.xls,удаляем Result.txt
        $this->managerFilesConverter();
        self::senderMailer();//отправляем результат на почту администратору
        self::deleteAllCreatedData();//удаляем все файлы,удаляем сессию
        return;
    }

    /**
     * метод отправления письма с вложением
     */
    public static function senderMailer(){
        $emailMessage=new Message();

        $bodyPart=new \Zend\Mime\Message();
        $attachment=new Part(fopen(self::XLS_NEW_FILE,'r'));
        $attachment->type = 'application/vnd.ms-excel';
        $attachment->filename="parse_data_".date('d-m-Y_H:i:s', time()).'.xls';
        $attachment->disposition=Mime::DISPOSITION_ATTACHMENT;
        $bodyPart->setParts(array($attachment));

        $emailMessage->addFrom("localhost@localhost.com", "Ренат Османов")
            ->addTo(AdministratorController::checkSessionCatchLog())//статический метод в аргументе возвращает значение  сессии с email администратора
            ->setSubject("Парсинг из Excel");
        $emailMessage->setBody($bodyPart);

        $transportEmailMessage=new SmtpTransport();
        $options=new SmtpOptions(array(
                                 'name' => 'localhost',
                                 'host' => 'smtp.gmail.com',
                                 'port'=> 587,
                                 'connection_class' => 'login',
                                 'connection_config' => array(
                                     'username' => 'renat.3259404',
                                     'password' => 'renat007',
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
        array_shift($arrayDataFromExcel);
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
                $code=trim((string)$secondEntry[1]);
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
        $this->_divideParams[$stringLink][$secondEntry[2]][]=$secondEntry[0];//1C
        $this->_divideParams[$stringLink][$secondEntry[2]][]=$code;//код конкурента
    }

    /**
     * заменяем self::TXT_OLD_FILE на self::XLS_NEW_FILE
     */
    public function managerFilesConverter(){
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

    /**
     * удаляем все загруженные,созданные файлы,удаляем сиссию и куки
     */
    public static function deleteAllCreatedData(){
        if(file_exists(self::XLS_NEW_FILE)){
            unlink(self::XLS_NEW_FILE);
        }
        if(file_exists(self::TXT_OLD_FILE)){
            unlink(self::TXT_OLD_FILE);
        }
        unlink(self::LOAD_FILE);
        $params = session_get_cookie_params();
        setcookie(session_name(), '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
        unset($_SESSION['user']);
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




}