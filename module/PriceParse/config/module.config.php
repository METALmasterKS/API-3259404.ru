<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
//    'router' => array(
//        'routes' => array(
//
//            // The following is a route to simplify getting started creating
//            // new controllers and actions without needing to create a new
//            // module. Simply drop new controllers in, and you can access them
//            // using the path /application/:controller/:action
//            'price' => array(
//                'type'    => 'Literal',
//                'options' => array(
//                    'route'    => '/price',
//                    'defaults' => array(
//                        '__NAMESPACE__' => 'PriceParse\Controller',
//                        'controller'    => 'Administrator',
//                        'action'        => 'index',
//                    ),
//                ),
//                'may_terminate' => true,
//                'child_routes' => array(
//                    'default' => array(
//                        'type'    => 'Segment',
//                        'options' => array(
//                            'route'    => '/[:controller[/:action]]',
//                            'constraints' => array(
//                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
//                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
//                            ),
//                            'defaults' => array(
//                            ),
//                        ),
//                    ),
//                ),
//            ),
//        ),
//    ),

    'router'=>array(
        'routes'=>array(
            'price'=>array(
                'type'=>'segment',
                'options'=>array(
                    'route'=>'/price[/:action][/:id]',

                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ),
                    'defaults'    => array(
                        'controller' => 'PriceParse\Controller\Administrator',
                        'action'     => 'index',
                    ),
                ),
            ),
            'komus'=>array(
                'type'=>'segment',
                'options'=>array(
                    'route'=>'/komus[/:action][/:id]',

                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ),
                    'defaults'    => array(
                        'controller' => 'PriceParse\Controller\AdministratorKomus',
                        'action'     => 'index',
                    ),
                ),
            ),
            'cron'=>array(
                'type'=>'segment',
                'options'=>array(
                    'route'=>'/cron[/:action][/:id]',

                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ),
                    'defaults'    => array(
                        'controller' => 'PriceParse\Controller\Cron',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),


    ),


    'controllers' => array(
        'invokables' => array(
            'PriceParse\Controller\Administrator' => 'PriceParse\Controller\AdministratorController',
            'PriceParse\Controller\AdministratorKomus' => 'PriceParse\Controller\AdministratorKomusController',
            'PriceParse\Controller\Cron' => 'PriceParse\Controller\CronController',
        ),
    ),

    'view_manager' => array(

        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),

    ),

);
