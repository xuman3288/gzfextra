<?php

namespace Gzfextra\Tool;

use Gzfextra\Mvc\GlobalModuleRouteListener;
use Zend\Console\Adapter\AbstractAdapter;
use Zend\Mvc\MvcEvent;

/**
 * Class Module
 *
 * @author  moln.xie@gmail.com
 */
class Module
{
    public function init()
    {
    }

    public function onBootstrap(MvcEvent $e)
    {
        $eventManager = $e->getApplication()->getEventManager();

        $gListener = new GlobalModuleRouteListener();
        $eventManager->attach($gListener);

        if (PHP_SAPI == 'cli') {
            /** @var \Zend\Mvc\Controller\ControllerManager $controllerLoader */
            $controllerLoader = $e->getApplication()->getServiceManager()->get('ControllerLoader');
            $controllerLoader->setInvokableClass(
                'Gzfextra\\Controller\\Console', 'Gzfextra\\Tool\\Controller\\ConsoleController'
            );
        }
    }

    public function getConfig()
    {
        if (PHP_SAPI == 'cli') {
            return include __DIR__ . '/../../../config/module.cli-config.php';
        }
        return GlobalModuleRouteListener::getDefaultRouterConfig();
    }

    public function getConsoleUsage(AbstractAdapter $console)
    {
        $result = array(
            'zf g create <tableName> <moduleName> [<db>] [-e|-re] [-t] [--name=] [--schema=]',

            array('-t',         'Generate table class'),
            array('-e|-re',     '-e Generate Entity , -re Use RowGateway'),
            array('--schema',   'select adapter schema.'),
            array('tableName',  'Table name'),
            array('name',       'Class name'),
            array('moduleName', 'Module name'),
            array('db',         '(optional)Db adapter.'),
        );
        return $result;

        /*
        if ($console->isUtf8()) {
            return $result;
        } else {
            next($result);
            while ($val = current($result)) {
                $result[key($result)][1] = iconv('utf-8', 'gbk', $result[key($result)][1]);
                next($result);
            }

            return $result;
        }
        */
    }
}
