<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace PriceParse\Controller;


use PriceParse\CronModel\CronModel;
use PriceParse\Form\UploadForm;
use PriceParse\Model\TableModel;
use Zend\Filter\File\Rename;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;


use PriceParse\FileModel\FileModel;
use PriceParse\FileModel\KomusFileModel;

/**
 * Class AdministratorController
 *  Парсим ресурсы по артикулу из заргуженного Excel файла.
 *  Результат сохраняем в новый Excel файл и отправляем администратору на почту.
 * @package PriceParse\Controller
 */
class AdministratorController extends AbstractActionController
{
    /**
     * ПРИ GET ЗАПРОСЕ
     * Загружаем форму с двумя инпутами через UploadForm:
     * -загрузка файла
     * -почта админа
     *
     * ПРИ ОТПРАВЛЕНИИ ФОРМЫ-метод POST
     * Валидация:
     * успех-обзываем загружаемый файл и кладем на сервер из временной директории в ./data/price_data_test.xls
     *       -создаем сессию в которую пишем почту админа,вписанную в input
     *       -редирект на messageAction
     * не удача-редирект на errorAction
     *
     * @return array|ViewModel
     */
    public function indexAction()
    {

        $request = $this->getRequest();

        $form = new UploadForm("upload-form");

        if ($request->isPost()) {
            //для загрузки файла на сервер необходимо склеить два массива POST и FILE
            $post = array_merge_recursive(
                $request->getPost()->toArray(), $request->getFiles()->toArray()
            );
            //склейку передаем в форму для валидации
            $form->setData($post);
            //проверяем на сущ-е файла ./data/price_data_test.xls,если есть то редирект на ошибку
            if (file_exists(FileModel::LOAD_TXT_FILE)) {
                $this->redirect()->toRoute('price', array('action' => 'error'));
            }

            if ($form->isValid()) {
                $request = new Request();
                $files = $request->getFiles(); //данные с массивом FILES
                $filter = new Rename(FileModel::LOAD_TXT_FILE); //имя для загруженного Excel файла
                $filter->filter($files['excel-file']); //привязываем имя
                $email = $request->getPost('admin-email');
                $user_session = new Container('user'); //создаем сессию
                $user_session->email = $email; //запись в сессию email из input
                $this->redirect()->toRoute('price', array('action' => 'message'));
            }
        }

        return new ViewModel(array('form' => $form));
    }





    /**
     * Успех
     * @return ViewModel
     */
    public function messageAction()
    {
        $session = new Container('user');
        if (!self::checkSessionCatchLog()) {
            $this->redirect()->toRoute('price', array('action' => 'index'));
        }
        //todo додумать еще варианты с ошибками
        if((self::checkSessionCatchLog() && file_exists(FileModel::TXT_OLD_FILE))){
           //FileModel::deleteAllCreatedData();
            $this->redirect()->toRoute('price', array('action' => 'index'));
        }
       // $message = 'В течение 30-40 мин. Вам на почту будет выслан готовый файл.';
        $message = 'Не закрывайте эту вкладку браузера,пока не появится сообщение "Через пару минут Вам придет письмо!"';

        return new ViewModel(array('param' => $message));
    }

    /**
     * Не удача
     * @return ViewModel
     */
    public function errorAction()
    {
        $message = 'Попробуйте зайти позже...';

        return new ViewModel(array('param' => $message));
    }

    /**
     * через fileWorkAction выполняется вся основная работа:
     *   парсинг,работа с файлами,отправка результата парсинга на почту
     *
     * из ViewModel messageAction,дергаем этот Action.
     * Делаем это потому что при долгом выполении кода приходит 504 заголовок,но код продолжает работать,чтобы никого не смущать сделали такую штуку
     * @return JsonModel
     */
    public function fileWorkAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $flag=$request->getPost('flag');
            $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
//todo перенести свич в модель сделать
            switch($flag){
                /**
                 * ПО КОНКУРЕНТАМ
                 */
                case('create'):
                    $user_session = new Container('user'); //создаем сессию
                    if(isset($user_session->email)){
                        $param=new TableModel($adminModel,$flag);
                        $result=new JsonModel(array('table_from_file_was_created',(string)$param->count));
                        return $result;
                    }
                    else{
                        return new JsonModel(array('ERROR!YOU HAVE NOT PERMISSION!!!'));
                    }
                case('insert_parse_data'):
                    //$fileModel=new FileModel($adminModel);
                    $exist=FileModel::checkIssetCompetitorsFile(FileModel::LOAD_TXT_FILE);
                    if(!$exist) {
                        $result=new JsonModel(array('deleted'));
                        return $result;
                    }
                    $user_session = new Container('user');
                    $user_session->email=$user_session->email;//чисто чтоб наверняка
                    $fileModel=new FileModel($adminModel);
                    if($fileModel->status=='complete'){
                        $result=new JsonModel(array('complete'));
                        return $result;
                    }
                    $result=new JsonModel(array('in_the_process'));
                    return $result;
                case('dump_table'):
                    $adapterModel=$this->getServiceLocator()->get('ModelAdapter');
                    $param=new TableModel($adminModel,$flag);


                    FileModel::senderMailer();//отправляем результат на почту администратору
                    FileModel::deleteAllCreatedData();//удаляем все файлы,удаляем сессию
                    $param->clearTableFromParseData();//очищаем табл от данных которые спарсили
                    //это очень плохо так делать не надо
                    $forIdstartOne=new TableModel($adapterModel);
                    $forIdstartOne->makeIdOne();//сбрасываем id в табл в единицу

                    $result=new JsonModel(array('ok'));
                    return $result;


            }


        }
    }





    /**
     * Проверка на существование загруженного файла
     *
     * @return JsonModel
     */
    public function checkIssetFileAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $flag=$request->getPost('flag');
            if($flag=='check'){
                $exist=FileModel::checkIssetCompetitorsFile(FileModel::LOAD_TXT_FILE);
                if($exist){
                    $result=new JsonModel(array('not_empty_dir'));
                    return $result;
                }
                $result=new JsonModel(array('empty_dir'));
                return $result;


            }

        }
    }
//удаление загруженных файлов по парсингу всех конкурентов
    public function deleteAllDataAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $flag=$request->getPost('flag');
            FileModel::deleteAllCreatedDataForButton();
            $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
            $adapterModel=$this->getServiceLocator()->get('ModelAdapter');
            if($flag=='parse_komus_diff_prices' ||  $flag=='parse_competitors_prices'){

                $param=new TableModel($adminModel);
                $param->clearTableFromParseData($flag);
                //это очень плохо так делать не надо
                $forIdstartOne=new TableModel($adapterModel);
                $forIdstartOne->makeIdOne($flag);//сбрасываем id в табл в единицу
               $message=array('ok');
            }
            else{
                $message=array('error_on_delete');
            }


            $result=new JsonModel($message);
            return $result;
        }
    }






    /**
     * Проверка на существование сессии,false в случае отсутствия
     * @return bool
     */
    public  static function checkSessionCatchLog()
    {
        $session = new Container('user');

        return (!$session->email) ? false : $session->email;
    }





//тестирование
    public function testAction(){

        $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
     //   $adapterModel=$this->getServiceLocator()->get('ModelAdapter');
       // $param=new TableModel($adminModel);
    //    $fileModel=new FileModel($adminModel);
        //$res= $param->dumpFile();
       // $res=KomusFileModel::komusTxtDataConvertFromExcelToTwoDimensionalArray(FileModel::LOAD_TXT_FILE);

        // $param=new TableModel($adminModel,'create','komus');//-создание комуса
//$res=new KomusFileModel($adminModel);парсинг с комуса
       // $param=new TableModel($adminModel,'dump_table','komus');дамб

       // return new ViewModel(array('test'=>'ok'));
        // getDataFromDbAfterCronWorkForPriceParseClientWithLoadFilePassport($table,array $columns,array $where){
        $test=KomusFileModel::getCsv(CronModel::USER_LOAD_SELECT_TXT,3);
        $testCheck=array_chunk($test,3);
        if(preg_match('/\D+/',$testCheck[0][0])){
            array_shift($testCheck);
        }
        $tableModel=new TableModel($adminModel);
        $result=array();
        foreach($testCheck as $column){
           $result=$tableModel->getDataFromDbAfterCronWorkForPriceParseClientWithLoadFilePassport('parse_competitors_prices',
                array('id','product_1c_code','competitor_code','articul_product','price_product','product_description'),
                array('product_1c_code'=>$column[0],'competitor_code'=>$column[1],'articul_product'=>$column[2]));
            $tableModel->processingAllCompetitorsResultFromDbToFile($result,CronModel::RESULT_SELECT_TXT_ON_USER);
        }
        return new ViewModel(array('test'=>$result));
    }





}
