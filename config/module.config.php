<?php
return array(
    'controllers' => [
        'invokables' => [
            'Test\Controller\Index' => 'Test\Controller\IndexController',
            'Test\Controller\User'  => 'Test\Controller\UserController'

        ]
    ],
    'router' => [
        'routes' => [
            'test' => [
                'type' => 'Literal',
                'options' => [
                    'route' => '/test',
                    'defaults' => [
                        '__NAMESPACE__' => 'Test\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index'
                    ]
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'default' => [
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ]
                ]
            ]
        ]
    ],
    'view_manager' => array(
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
        ),
        'template_path_stack' => array(
            'test' => __DIR__ . '/../view',
        ),
        'strategies' => array('ViewJsonStrategy')
    ),
    'service_manager' => array(
        'factories' => array(
        ),
        'aliases'   => array(
        ),
        'abstract_factories'    => array(
        )
    )
);