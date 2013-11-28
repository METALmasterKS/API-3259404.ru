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
use Zend\Filter\File\Rename;
use Zend\Http\PhpEnvironment\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Session\Container;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

use PriceParse\FileModel\FileModel;

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
            if (file_exists(FileModel::LOAD_FILE)) {
                $this->redirect()->toRoute('price', array('action' => 'error'));
            }

            if ($form->isValid()) {
                $request = new Request();
                $files = $request->getFiles(); //данные с массивом FILES
                $filter = new Rename(FileModel::LOAD_FILE); //имя для загруженного Excel файла
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
        if((self::checkSessionCatchLog() && file_exists(FileModel::TXT_OLD_FILE)) || (self::checkSessionCatchLog() && file_exists(FileModel::XLS_NEW_FILE))){
           FileModel::deleteAllCreatedData();
            $this->redirect()->toRoute('price', array('action' => 'error'));
        }
        $message = 'В течение 30-40 мин. Вам на почту будет выслан готовый файл.';

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
            if($flag=='start'){
                //получаем PHP массив данных из excel файла,подготавливаем массив для метода  letsParseWorkingLinks
                $fileModel=new FileModel();
                //по подготовленному массиву в конструкторе,парсим ресурсы,отправляем результат почту
                $fileModel->letsParseWorkingLinks();
                //можно было бы и убрать,пользователь получит этот 'hello' если не произойдет 504 и если дождется конца выполнения парсинга
                $result=new JsonModel(array('hello'));
                return $result;
            }
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

}
