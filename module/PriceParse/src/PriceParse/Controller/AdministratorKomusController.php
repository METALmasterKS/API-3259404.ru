<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace PriceParse\Controller;


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
class AdministratorKomusController extends AbstractActionController
{


    public function indexAction(){
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
            if (file_exists(FileModel::LOAD_TXT_FILE_KOMUS)) {
                $this->redirect()->toRoute('price', array('action' => 'error'));
            }

            if ($form->isValid()) {
                $request = new Request();
                $files = $request->getFiles(); //данные с массивом FILES
                $filter = new Rename(FileModel::LOAD_TXT_FILE_KOMUS); //имя для загруженного Excel файла
                $filter->filter($files['excel-file']); //привязываем имя
                $email = $request->getPost('admin-email');
                $user_session_komus = new Container('userkomus'); //создаем сессию
                $user_session_komus->email = $email; //запись в сессию email из input
                $this->redirect()->toRoute('komus', array('action' => 'messagekomus'));
            }

        }
        return new ViewModel(array('form' => $form));
    }


    public function messageKomusAction(){
        $session = new Container('userkomus');
        if (!self::checkSessionCatchLogKomus()) {
            $this->redirect()->toRoute('price', array('action' => 'komus'));
        }
        //todo додумать еще варианты с ошибками
        if((self::checkSessionCatchLogKomus() && file_exists(FileModel::TXT_OLD_FILE_KOMUS))){
            //FileModel::deleteAllCreatedData();
            $this->redirect()->toRoute('price', array('action' => 'komus'));
        }
        // $message = 'В течение 30-40 мин. Вам на почту будет выслан готовый файл.';
        $message = 'Парсинг цен комуса.Не закрывайте эту вкладку браузера,пока не появится сообщение "Через пару минут Вам придет письмо!"';

        return new ViewModel(array('param' => $message));
    }


    public function fileWorkKomusAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $flag=$request->getPost('flag');
            $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');

            /**
             * ПО КОМУСУ
             */
            switch($flag){
                case('create_komus'):
                    $user_session_komus = new Container('userkomus');
                    if(isset($user_session_komus->email)){
                        $param=new TableModel($adminModel,$flag,'komus');//-создание комуса
                        $result=new JsonModel(array('table_from_file_was_created',(string)$param->count));
                        return $result;
                    }
                    else{
                        return new JsonModel(array('ERROR!YOU HAVE NOT PERMISSION!!!'));
                    }
                case('insert_parse_data_komus'):
                    $exist=FileModel::checkIssetCompetitorsFile(FileModel::LOAD_TXT_FILE_KOMUS);
                    if(!$exist) {
                        $result=new JsonModel(array('deleted'));
                        return $result;
                    }
                    $user_session = new Container('userkomus');
                    $user_session->email=$user_session->email;//чисто чтоб наверняка
                    $fileModel=new KomusFileModel($adminModel);//парсинг с комуса
                    if($fileModel->status=='complete'){
                        $result=new JsonModel(array('complete'));
                        return $result;
                    }
                    $result=new JsonModel(array('in_the_process'));
                    return $result;
                case('dump_table_komus'):
                    $adapterModel=$this->getServiceLocator()->get('ModelAdapter');
                    $param=new TableModel($adminModel,$flag,'komus');
                    FileModel::senderMailer(FileModel::TXT_OLD_FILE_KOMUS,'parse_data_komus');//отправляем результат на почту администратору
                    FileModel::deleteAllCreatedData(FileModel::LOAD_TXT_FILE_KOMUS,FileModel::TXT_OLD_FILE_KOMUS,$sessName='userkomus');//удаляем все файлы,удаляем сессию
                    $param->clearTableFromParseData('parse_komus_diff_prices');//очищаем табл от данных которые спарсили
                    //это очень плохо так делать не надо
                    $forIdstartOne=new TableModel($adapterModel);
                    $forIdstartOne->makeIdOne('parse_komus_diff_prices');//сбрасываем id в табл в единицу
                    $result=new JsonModel(array('ok'));
                    return $result;
            }
        }

    }

    public function errorAction()
    {
        $message = 'Попробуйте зайти позже...';

        return new ViewModel(array('param' => $message));
    }
    public  static function checkSessionCatchLogKomus()
    {
        $session = new Container('userkomus');

        return (!$session->email) ? false : $session->email;
    }

}
