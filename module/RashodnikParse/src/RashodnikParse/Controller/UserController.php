<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace RashodnikParse\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Zend\Json\Json;


use RashodnikParse\Form\CartridgeForm;

class UserController extends AbstractActionController
{
    public function indexAction()
    {
        $printerModel=$this->getServiceLocator()->get('RashodnikParse\Model\PrinterModel');
        $dataFromDB=$printerModel->getBrands();

        /*создаем форму с элементами select,первый аргумент имя формы,второй для заполнения элементов select значениями массива $dataFromDB*/
        $form=new CartridgeForm('cartridge_form',$dataFromDB);

        return new ViewModel(array('form'=>$form));
    }


    /*получение всех брендов id=>brandName*/
    public function getBrandsAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){

            $printerModel=$this->getServiceLocator()->get('RashodnikParse\Model\PrinterModel');
            $dataFromDB=$printerModel->getBrands();
            $result = new JsonModel($dataFromDB);

            return $result;

        }

    }



    /*получаем типы принтеров по номеру бренда,для передачи через AJAX в массиве из JSON*/
    public function getTypesAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $id=(int)$request->getPost('id');
            if($id){
                $printerModel=$this->getServiceLocator()->get('RashodnikParse\Model\PrinterModel');
                $dataToClient=$printerModel->getPrinterTypes($id);

                /*если результат из модели пришел*/
                if($dataToClient){
                    $result = new JsonModel($dataToClient);
                    return $result;
                }
            }



        }
    }



    /*получаем серии принтеров  только по номеру бренда, или по номеру бренда и по номеру типа принтера, для передачи через AJAX в массиве из JSON*/
    public function getSeriesAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $id=(int)$request->getPost('id');
            $idType=(int)$request->getPost('idType');
            $printerModel=$this->getServiceLocator()->get('RashodnikParse\Model\PrinterModel');
            if(empty($idType)){
                $dataPsToClient=$printerModel->getSeriesOnlyForBrand($id);
            }
            if(!empty($id) && !empty($idType)){
                $dataPsToClient=$printerModel->getPrecisionSeriesForType($id,$idType);
            }
            /*если результат из модели пришел*/
            if($dataPsToClient){
                $result = new JsonModel($dataPsToClient);
                return $result;
            }
        }
    }

    /*получаем  модели принтеров(вместе с сериями и номерами принтеров) по номеру бренда,
    или по номеру бренда и по номеру типа принтера,
    или по номеру бренда,по номеру типа принтера и по серии принтера*/

    public function getModelsAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $idBrand=(int)$request->getPost('idBrand');
            $idType = $request->getPost('idType') == null ? null : (int) $request->getPost('idType');
            $idSeries=(int)$request->getPost('idSeries');

            $printerModel=$this->getServiceLocator()->get('RashodnikParse\Model\PrinterModel');

            if(empty($idType) && empty($idSeries)){
                if(!empty($idBrand)){
                    $dataPmToClient=$printerModel->getModelsOnlyForBrand($idBrand);
                }
            }

            elseif(empty($idSeries)){
                if(!empty($idBrand)){
                    $dataPmToClient=$printerModel->getPrecisionModelsForType($idBrand,$idType);
                }
            }

            else{
                if(!empty($idBrand)){
                    $dataPmToClient=$printerModel->getPrecisionModelsForSeries($idBrand,$idType,$idSeries);
                }
            }



            if($dataPmToClient){
                $result = new JsonModel($dataPmToClient);

                return $result;
            }

        }
    }
    /*получаем картриджи по id моделей принтеров*/
    public function getCartridgesAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){

            $idModel=(int)$request->getPost('idModel');

            $printerModel=$this->getServiceLocator()->get('RashodnikParse\Model\PrinterModel');
            $dataToClient=$printerModel->getCartridgesForModel($idModel);

            $result = new JsonModel($dataToClient);

            return $result;
        }


    }
    /*получаем модели принтеров по long_number картриджей*/
    public function getPrintersForLongNumberAction(){
        $request=$this->getRequest();
        if($request->isXmlHttpRequest()){
            $longNum=(string)$request->getPost('longNum');
            if(!empty($longNum)){

                $printerModel=$this->getServiceLocator()->get('RashodnikParse\Model\PrinterModel');
                $dataToClient=$printerModel->getPrecisionPrintersForLongNumber($longNum);
            }
            else{
                $dataToClient=array('ERROR-check longNum on client side');
            }
            $result = new JsonModel($dataToClient);
            return $result;

        }
    }

}


