<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace RashodnikParse\Controller;

use RashodnikParse\RenatsClassesLib\GetAllBrands;
use Zend\Dom\Query;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Driver\DriverInterface;
use Zend\Console\Request as ConsoleRequest;


class AdminController extends AbstractActionController
{



    public function indexAction()
    {
        return new ViewModel(array());
    }







    public function updateAction()
    {

        $request=$this->getRequest();

        if($request->isXmlHttpRequest()){
            $resourceBrands=new GetAllBrands();
            //все бренды с парсинга,тут массив  [brand]=>url первой страницы бренда,нужно для получение всех типов,т.к берем все эти данные из первой страницы
            $parseBrandsFirstPagesUrl=$resourceBrands->readInfoFromBrands();

            $adminModel=$this->getServiceLocator()->get('RashodnikParse\Model\AdminModel');
            $brandsFromDB=$adminModel->getBrandsFromDb();//все бренды из базы

            /*сравниваем данные из парсинга и из базы по брендам,если в базе нет какого-то бренда какой есть в парсе то вставляем этот бренд в базу*/
            $adminModel->putBrandsToDbIfResourceDifference($brandsFromDB,$parseBrandsFirstPagesUrl);

            /*парсинг по всем первым страницам по URL из $parseBrandsFirstPagesUrl,из первой страницы получаем все типы принтеров,получаем все типы из базы,синхронизируем данные из базы по данным из парсинга
            так же получаем  все ссылки на табл.совместимости,парсим все табл.совместимости,записываем результат в базу
            */
            $adminModel->resourcedDomModelGateway($parseBrandsFirstPagesUrl);

        }

        return new ViewModel();





    }


}




