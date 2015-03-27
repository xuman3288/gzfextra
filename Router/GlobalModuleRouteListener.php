<?php

namespace Gzfextra\Mvc;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Mvc\MvcEvent;


/**
 * Class GlobalModuleRouteListener
 * @author moln.xie@gmail.com
 */
class GlobalModuleRouteListener implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    const ORIGINAL_CONTROLLER = '__CONTROLLER__';

    public static function getDefaultRouterConfig()
    {
        return array(
            'router' => array(
                'routes' => array(
                    'module' => array(
                        'type'         => 'segment',
                        'options'      => array(
                            'route'       => '/:module[/][:controller[/:action]]',
                            'constraints' => array(
                                'module'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults'    => array(
                                'controller' => 'index',
                                'action'     => 'index',
                            ),
                        ),
                        'child_routes' => array(
                            'params' => array(
                                'type' => 'Wildcard',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onRoute'));
    }

    /**
     * Global Route /module/ctrl/action
     * @param MvcEvent $e
     */
    public function onRoute(MvcEvent $e)
    {
        $matches    = $e->getRouteMatch();
        $module     = $matches->getParam('module');
        $controller = $matches->getParam('controller');

        if ($module && $controller && strpos($controller, '\\') === false) {

            //ZF2 ModuleRouteListener::ORIGINAL_CONTROLLER
            $matches->setParam(self::ORIGINAL_CONTROLLER, $controller);

            /** @var \Zend\Mvc\Controller\ControllerManager $controllerLoader */
            $controllerLoader = $e->getApplication()->getServiceManager()->get('ControllerLoader');

            $ctrlClass = ucfirst($module) . '\\Controller\\';
            $ctrlClass .= str_replace(' ', '', ucwords(str_replace('-', ' ', $controller)));
            $controller = $ctrlClass;
            $matches->setParam('controller', $controller);

            $ctrlClass .= 'Controller';
            if (!$controllerLoader->has($controller) && class_exists($ctrlClass)) {
                $controllerLoader->setInvokableClass($controller, $ctrlClass);
                $e->setController($controller);
                $e->setControllerClass($ctrlClass);
            }
        }
    }
}