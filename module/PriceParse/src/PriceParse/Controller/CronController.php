<?php
/**
 * Created by PhpStorm.
 * User: Renat Osmanov
 * Email: rhenium.osmium@gmail.com
 * Date: 19.12.13
 */
namespace PriceParse\Controller;

use PriceParse\CronModel\CronModel;
use PriceParse\CronModel\UserSelectCronModel;
use PriceParse\FileModel\CronAllFileModel;
use PriceParse\FileModel\CronKomusFileModel;
use PriceParse\FileModel\FileModel;
use PriceParse\Form\UploadCronForm;
use PriceParse\Model\TableModel;
use Zend\Http\Headers;
use Zend\Http\Response\Stream;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Console\Request as ConsoleRequest;
use Zend\Http\PhpEnvironment\Request;

use Zend\Filter\File\Rename;
use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;


class CronController extends AbstractActionController
{
//todo очистить базу при  загрузке нового файла для крон
//загрузка файла для cron
    public function indexAction(){
        $request = $this->getRequest();
        $form = new UploadCronForm("upload-cron-form");
        if ($request->isPost()) {
            //для загрузки файла на сервер необходимо склеить два массива POST и FILE
            $post = array_merge_recursive(
                $request->getPost()->toArray(), $request->getFiles()->toArray()
            );
            //склейку передаем в форму для валидации
            $form->setData($post);
            //проверяем на сущ-е файла ./data/price_data_test.xls,если есть то редирект на ошибку
            if (file_exists(CronModel::CRON_LOAD_TXT_FILE_KOMUS)) {
                FileModel::deleteAllCreatedData(CronModel::CRON_LOAD_TXT_FILE_KOMUS);
            }

            if ($form->isValid()) {
                $request = new Request();
                $files = $request->getFiles(); //данные с массивом FILES
                $filter = new Rename(CronModel::CRON_LOAD_TXT_FILE_KOMUS); //имя для загруженного TXT файла для работы с кроном
                $filter->filter($files['txt-file-for-crone']); //привязываем имя
                $this->redirect()->toRoute('cron', array('action' => 'message'));
            }

        }
        return new ViewModel(array('form' => $form));

    }

    public function messageAction(){
        return new ViewModel(array('message' =>'Подождите...' ));
    }
//загрузка файла пользователя на select из таблицы которая была за ночь сделана кроном на ВСЕХ конкурентов
    public function loadAction(){
        $request = $this->getRequest();
        $form = new UploadCronForm("upload-cron-form");
        if ($request->isPost()) {
            //для загрузки файла на сервер необходимо склеить два массива POST и FILE
            $post = array_merge_recursive(
                $request->getPost()->toArray(), $request->getFiles()->toArray()
            );
            //склейку передаем в форму для валидации
            $form->setData($post);
            //удаляем старый файл с запросом если он есть
            if (file_exists(CronModel::USER_LOAD_SELECT_TXT)) {
//                $cronDataDir=scandir('./data/cronData');
//                $oldFileNameForUser=FileModel::getFileLikeStartNameFromScanDir('allPriceResult',$cronDataDir);
                FileModel::deleteAllCreatedDataForButton(CronModel::USER_LOAD_SELECT_TXT,CronModel::RESULT_SELECT_TXT_ON_USER);
            }

            if ($form->isValid()) {
                $request = new Request();
                $files = $request->getFiles(); //данные с массивом FILES
                $filter = new Rename(CronModel::USER_LOAD_SELECT_TXT); //имя для загруженного TXT файла для работы с кроном
                $filter->filter($files['txt-file-for-crone']); //привязываем имя
                $this->redirect()->toRoute('cron', array('action' => 'result'));
            }

        }
        return new ViewModel(array('form' => $form));

    }

    //todo узнать А НАДО ЛИ такую выгрузку делать по КОМУСУ?
    //загрузка файла пользователя на select из таблицы которая была за ночь сделана кроном
    public function loadKomusAction(){
        $request = $this->getRequest();
        $form = new UploadCronForm("upload-cron-form");
        if ($request->isPost()) {
            //для загрузки файла на сервер необходимо склеить два массива POST и FILE
            $post = array_merge_recursive(
                $request->getPost()->toArray(), $request->getFiles()->toArray()
            );
            //склейку передаем в форму для валидации
            $form->setData($post);
            if (file_exists(CronModel::USER_LOAD_SELECT_TXT)) {
                FileModel::deleteAllCreatedDataForButton(CronModel::USER_LOAD_SELECT_TXT,CronModel::RESULT_SELECT_TXT_ON_USER);
            }

            if ($form->isValid()) {
                $request = new Request();
                $files = $request->getFiles(); //данные с массивом FILES
                $filter = new Rename(CronModel::USER_LOAD_SELECT_TXT); //имя для загруженного TXT файла для работы с кроном
                $filter->filter($files['txt-file-for-crone']); //привязываем имя
                $this->redirect()->toRoute('cron', array('action' => 'result'));
            }

        }
        return new ViewModel(array('form' => $form));

    }





//выдача ссылки на скачку
    public function resultAction(){
        return new ViewModel(array('message' =>'Подождите,сейчас появится ссылка на загрузку результата' ));
    }

//по загруженному файлу CronModel::USER_LOAD_SELECT_TXT осуществляется выборка,результат записывается в CronModel::RESULT_SELECT_TXT_ON_USER
    public function selectOnCronResultAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $flag=$request->getPost('flag');
            if($flag=='select'){
                $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
                $userSelect=new UserSelectCronModel($adminModel);
                $userSelect->recordDataForUserFileSelect(CronModel::USER_LOAD_SELECT_TXT,CronModel::RESULT_SELECT_TXT_ON_USER,3);
              //  $userSelect->recordDataForUserFileSelect(CronModel::USER_LOAD_SELECT_TXT,CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS,3);

                return new JsonModel(array('result_file_was_created'));

            }
        }
    }

    /**
     * открывает на загрузку пользователю файл-результат по ссылке
     *
     * @return Stream
     */
    public function downloadAction() {
        $fileName = CronModel::RESULT_SELECT_TXT_ON_USER;
        //$fileName = CronModel::RESULT_SELECT_TXT_ON_USER_KOMUS;
       // $fileNameForUser='./data/cronData/allPriceResult'.date('-d-m-Y', time()).'.txt';
//        if(copy($fileName,$fileNameForUser)){
//            unlink($fileName);
//        }
//        $filter = new Rename($fileName); //имя для загруженного TXT файла для работы с кроном
//        $filter->filter(CronModel::RESULT_SELECT_TXT_ON_USER); //привязываем имя

        $response=new Stream();
        $response->setStream(fopen($fileName, 'r'));
        $response->setStatusCode(200);

        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'file/octet-stream')
                ->addHeaderLine('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->addHeaderLine('Content-Length', filesize($fileName));

        $response->setHeaders($headers);

        return $response;
    }

    /**
     * Insert по загруженному файлу в табл для слепка перед парсингом
     *
     * @return JsonModel
     */
    //todo удалить, заменили методами komusAction и allAction,удалить вьюхи этого экшна
    public function putFileMaskIntoTableInDbAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
            $flag=$request->getPost('flag');
            if($flag=='komus' || $flag=='all'){
                if($flag=='komus'){
                    $model=new TableModel($adminModel,'create_crone_komus_table','komus');//-создание комуса
                    $filename=CronModel::CRON_LOAD_TXT_FILE_KOMUS;
                }
                if($flag=='all'){
                    $model=new TableModel($adminModel,'create_crone_table');
                    $filename=CronModel::CRON_LOAD_TXT_FILE;
                }
                FileModel::deleteAllCreatedData($filename);
                return new JsonModel(array('created',(string)$model->count));
            }
        }
    }


    /**
     * метод для работы приложени с кроном
     *
     * @throws \RuntimeException
     */
    public function cronAction(){
        $request = $this->getRequest();
        $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
//        if (!$request instanceof ConsoleRequest){
//            throw new \RuntimeException('You can only use this action from a console!');
//        }
        $cronItemKomus = $request->getParam('komus');
//проверка на заполнение таблицы по шаблону с файла txt,подготовка к парсингу
        $cronStatusOrder = $request->getParam('start');

        if($cronStatusOrder){
            $cronModel= new CronModel($adminModel);
            if($cronItemKomus){
                $statusMessage=$cronModel->setStatusAnswer('komus');
            }
            else{
                $statusMessage=$cronModel->setStatusAnswer('all');
            }
            $cronModel->chooseFutureCronMoon($statusMessage);

        }
    }

    /**
     * адаптер под CRON-парсинг КОМУС
     *
     * @return bool
     */
    public function komusAction(){
		$adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
        if(CronKomusFileModel::checkFileKomusLastModify($adminModel,'./data/logger/PriceParse/cron/lastModifyKomus.log')){
            $this->downloadKomusPriceFromFtp($adminModel);

        }

        $request = $this->getRequest();
        if($request->isGet()){
                $cronModel= new CronModel($adminModel);
                $statusMessage=$cronModel->setStatusAnswer('komus');
                $cronModel->chooseFutureCronMoon($statusMessage);
        }

        return false;
    }
	
	public function fakeKomusAction(){
		$request = $this->getRequest();
		 if($request->isGet()){
               CronKomusFileModel::clearFileKomusLastModify('./data/logger/PriceParse/cron/lastModifyKomus.log');
        }
        return false;
	}

     /**
     * адаптер под CRON-парсинг ВСЕХ конкурентов
     *
     * @return bool
     */
    public function allAction(){

        $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
/*
         if(CronAllFileModel::checkFileAllLastModify($adminModel,'./data/logger/PriceParse/cron/lastModifyAll.log')){
            $this->downloadAllCompetitorsPricesFromFtp($adminModel);
        }
        $request = $this->getRequest();
        if($request->isGet()){
                $cronModel= new CronModel($adminModel);
                $statusMessage=$cronModel->setStatusAnswer('all');
                $cronModel->chooseFutureCronMoon($statusMessage);
        }*/


       if(CronAllFileModel::checkFileAllLastModify($adminModel,'./data/logger/PriceParse/cron/lastModifyAll.log')){
            $this->downloadAllCompetitorsPricesFromFtp($adminModel);
       }

        $request = $this->getRequest();
        if($request->isGet()){
            $cronModel= new CronModel($adminModel);
            $statusMessage=$cronModel->setStatusAnswer('all');
            $cronModel->chooseFutureCronMoon($statusMessage);
        }

        return false;
    }



    /**
     * адаптер под CRON-скачивает с фтп 325 TXT файл сохраняет в ./data/cronData/cron_price_data_komus.txt,
     * делает слепок в таблицу parse_komus_diff_prices
     *
     */
    public function downloadKomusPriceFromFtp($adminModel){
       // $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
        $this->garbageManager($adminModel,CronModel::TABLE_NAME_KOMUS_PARSE);//чистим таблицу от старых данных в БД,сбрасываем id таблицы в 1
        $cronFtpModel=new CronKomusFileModel($adminModel);//скачиваем файл с фтп и подготавливаем таблицу parse_komus_diff_prices к парсингу с комуса
      //  return false;
    }

    /**
     * адаптер под CRON-скачивает с нашего фтп ТХТ файл сохраняет в ./data/cronData
     *
     * @param $adminModel
     *
     * @return bool
     */
    public function downloadAllCompetitorsPricesFromFtp($adminModel){
        $this->garbageManager($adminModel,CronModel::TABLE_NAME_ALL_PARSE);//чистим таблицу от старых данных в БД,сбрасываем id таблицы в 1
        $cronFtpModel=new CronAllFileModel($adminModel);//скачиваем файл с фтп  и подготавливаем таблицу parse_competitors_prices_cron к парсингу со всех конкурентов
        //return false;
    }

    /**
     * Чистим таблицу БД от старых данных,сбрасываем id таблицы в 1
     *
     * @param  $adminModel  адаптер
     *
     * @param   string  $table  имя таблицы
     */
    private function garbageManager($adminModel,$table){
        $adapterModel=$this->getServiceLocator()->get('ModelAdapter');
        $param=new TableModel($adminModel);
        $param->clearTableFromParseData($table);
        //это очень плохо так делать не надо
        $forIdstartOne=new TableModel($adapterModel);
        $forIdstartOne->makeIdOne($table);//сбрасываем id в табл в единицу
    }
}