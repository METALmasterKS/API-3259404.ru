<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

return array(
    'controllers' => array(
        'invokables' => array(
            'RashodnikParse\Controller\Admin' => 'RashodnikParse\Controller\AdminController',
            'RashodnikParse\Controller\User' => 'RashodnikParse\Controller\UserController',
        ),
    ),


    'router' => array(
        'routes' => array(

            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /application/:controller/:action
            'rashodnik' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/rashodnik',
                    'defaults' => array(
                        '__NAMESPACE__' => 'RashodnikParse\Controller',
                        'controller'    => 'Admin',
                        'action'        => 'index',
                    ),
                ),
                'may_terminate' => true,//что значит ?
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action[/:id]]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'show-users' => array(
                    'options' => array(
                        'route'    => 'update [--all|-a] rashodniki',
                        'defaults' => array(
                            'controller' => 'RashodnikParse\Controller\Admin',
                            'action'     => 'console'
                        )
                    )
                ),
                'cron-slave' => array(
                        'options' => array(
                            'route'    => 'price parse (--start) (komus|all)',
                            'defaults' => array(
                                '__NAMESPACE__' => 'PriceParse\Controller',
                                'controller' => 'Cron',
                                'action'     => 'cron',
                            )
                        )
                    ),
            )
        )
    ),
    'view_manager' => array(

        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
);
