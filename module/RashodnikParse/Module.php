<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace RashodnikParse;

use Zend\Db\Sql\Sql;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

use RashodnikParse\Model\PrinterModel;
use RashodnikParse\Model\AdminModel;


use Zend\ModuleManager\ModuleManager;
class Module
{


    /*public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }*/
/*где находится конф файл моего модуля*/
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
    public function getServiceConfig()
    {
        return array(

            'factories'=>array(
                'RashodnikParse\Model\PrinterModel'=>function($sm){
                    $sql=new Sql($sm->get('ModelAdapter'));
                    $model=new PrinterModel($sql);
                    return $model;
                },
                'RashodnikParse\Model\AdminModel'=>function($sm){
                    $sql=new Sql($sm->get('ModelAdapter'));
                    $model=new AdminModel($sql);
                    return $model;
                },
                'ModelAdapter'=>function($sm){
                    return $sm->get('Zend\Db\Adapter\Adapter');
                }
            ),



        );
    }
    public function init(ModuleManager $moduleManager) {
        $sharedEvents = $moduleManager->getEventManager()->getSharedManager();
        $sharedEvents->attach(__NAMESPACE__, 'dispatch', function($e) {
                $controller = $e->getTarget();
                $controller->layout('childrenLayout');
            }, 100);
    }
}
