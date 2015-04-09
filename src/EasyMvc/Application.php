<?php
namespace Gzfextra\EasyMvc;

use Gzfextra\Stdlib\InstanceTrait;
use Zend\EventManager\EventManagerInterface;
use Zend\Http\Header\MultipleHeaderInterface;
use Zend\Http\Response;
use Zend\I18n\Translator\Translator;
use Zend\Mvc\ApplicationInterface;
use Zend\Mvc\Exception\InvalidControllerException;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\RouteMatch;
use Zend\Mvc\Service\ServiceManagerConfig;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

/**
 * Class Application
 *
 * @author  moln.xie@gmail.com
 */
class Application implements ApplicationInterface
{
    use InstanceTrait;

    protected $config;
    protected $event;
    protected $request;
    protected $response;
    protected $eventManager;
    protected $serviceManager;

    protected $modules = [];

    public static function init(array $config)
    {

        if (isset($config['module_listener_options']['config_glob_paths'])) {
            foreach ($config['module_listener_options']['config_glob_paths'] as $path) {
                foreach (glob($path, GLOB_BRACE) as $file) {
                    if ($tmp = include $file) {
                        $config = ArrayUtils::merge($config, $tmp);
                    }
                }
            }
        }

        return self::getInstance($config);
    }

    public function __construct(array $config)
    {
        $this->config  = $config;
        $this->modules = isset($config['modules']) ? $config['modules'] : [];

        $smConfig                        = $this->getConfig('service_manager');
        $this->config['service_manager'] = $smConfig = ArrayUtils::merge($smConfig, $this->defaultServiceConfig);
        $this->config                    = new \ArrayObject($this->config);
        $this->serviceManager            = new ServiceManager(new ServiceManagerConfig($smConfig));
        $this->serviceManager->setService('ApplicationConfig', $config);
//        $this->serviceManager->get('ModuleManager');
        $this->serviceManager->setService('Config', $this->config);
    }

    public function getTranslator()
    {
        return Translator::factory($this->getConfig('translator'));
    }

    public function getConfig($key = null)
    {
        if ($key === null) {
            return $this->config;
        }
        return isset($this->config[$key]) ? $this->config[$key] : null;
    }

    private function notFound($message = null)
    {
        header("HTTP/1.0 404 Not Found");
//        if (APPLICATION_ENV != 'production') {
        echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<!-- $message -->
</body></html>
EOT;
//        }
        exit;
    }

    public function run()
    {
        $sm = $this->getServiceManager();
        $e  = $this->event = new MvcEvent();
        $e->setApplication($this);

        $this->request  = $sm->get('Request');
        $this->response = $sm->get('Response');

        $this->route();

        $this->loadModule();

        try {
            $result = $this->dispatch();

            if (!is_object($result)) {
                header('Content-Type: application/json');
                echo json_encode($result);
            } else if ($result instanceof JsonModel) {
                header('Content-Type: application/json');
                echo $result->serialize();
            } else if ($result instanceof ViewModel) {
                if ($this->response->getStatusCode() == 404) {
                    $this->notFound('Class not found');
                } else {
                }
            } else if ($result instanceof Response) {
                $this->sendResponse($result);
            }
        } catch (\Exception $e) {
            header('Content-Type: text/html');
            throw $e;
        }
    }

    private function sendResponse(Response $response)
    {
        foreach ($response->getHeaders() as $header) {
            if ($header instanceof MultipleHeaderInterface) {
                header($header->toString(), false);
                continue;
            }
            header($header->toString());
        }

        $status = $response->renderStatusLine();
        header($status);

        echo $response->getContent();
    }

    public function loadModule()
    {
        $e      = $this->getEvent();
        $module = $e->getRouteMatch()->getParam('module_name');

        if (in_array($module, $this->modules)) {
            $moduleClassName = $module . '\Module';
            $module          = new $moduleClassName;

            if (method_exists($module, 'getConfig') && ($moduleConfig = $module->getConfig())) {
                $this->config->exchangeArray(ArrayUtils::merge($this->config->getArrayCopy(), $moduleConfig));
            }

            if (method_exists($module, 'init')) {
                $module->init($e);
            }

            if (method_exists($module, 'onBootstrap')) {
                $module->onBootstrap($e);
            }
        }
    }

    private function dispatch()
    {
        $e                = $this->getEvent();
        $routeMatch       = $e->getRouteMatch();
        $controllerName   = $routeMatch->getParam('controller', 'not-found');
        $application      = $e->getApplication();
        $controllerLoader = $application->getServiceManager()->get('ControllerManager');

        if (!$controllerLoader->has($controllerName)) {
            return $this->notFound($controllerName);
        }

        try {
            /** @var \Zend\Mvc\Controller\AbstractActionController $controller */
            $controller = $controllerLoader->get($controllerName);
        } catch (InvalidControllerException $exception) {
            return $this->notFound($exception->getMessage());
        }

        $request  = $this->getRequest();
        $response = $this->getResponse();

        if (method_exists($controller, 'init')) {
            $controller->init();
        }
        $controller->setEvent($e);

        return $controller->dispatch($request, $response);
    }

    /**
     * Get the attached event
     *
     * Will create a new MvcEvent if none provided.
     *
     * @return MvcEvent
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return RouteMatch
     */
    private function route()
    {
        $request = $this->getRequest();

        $path = substr($request->getUri()->getPath(), strlen($request->getBasePath()));

        list($module, $controller, $action) =
            array_values(array_filter(explode('/', $path))) + ['Core', 'index', 'index'];

        $moduleName = ucfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $module))));

        $ctrlClass = $moduleName . '\\Controller\\';
        $ctrlClass .= str_replace(' ', '', ucwords(str_replace('-', ' ', $controller)));
        $controller = $ctrlClass;
        $ctrlClass .= 'Controller';

        $e = $this->getEvent();

        $rm = new RouteMatch(
            array(
                'module'      => $module,
                'module_name' => $moduleName,
                'controller'  => $controller,
                'action'      => $action,
            )
        );

        $e->setRouteMatch($rm);

        $controllerLoader = $this->getServiceManager()->get('ControllerLoader');
        if (!$controllerLoader->has($controller) && class_exists($ctrlClass)) {
            $controllerLoader->setInvokableClass($controller, $ctrlClass);
        }

        $e->setController($controller);
        $e->setControllerClass($ctrlClass);
    }

    /**
     * Get the locator object
     *
     * @return \Zend\ServiceManager\ServiceManager
     */
    public function getServiceManager()
    {
        return $this->serviceManager;
    }

    /**
     * Get the request object
     *
     * @return \Zend\Http\PhpEnvironment\Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get the response object
     *
     * @return \Zend\Http\PhpEnvironment\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        return $this->eventManager;
    }

    protected $defaultServiceConfig = array(
        'invokables'         => array(
//            'RouteListener'        => 'Zend\Mvc\RouteListener',
            'SendResponseListener' => 'Zend\Mvc\SendResponseListener',
            'Request'              => 'Gzfextra\Mvc\EasyMvc\Http\Request',
//            'Request'                       => 'Zend\Http\PhpEnvironment\Request',
            'Response'             => 'Zend\Http\PhpEnvironment\Response',
        ),
        'factories'          => array(
            'ControllerLoader'        => 'Zend\Mvc\Service\ControllerLoaderFactory',
            'ControllerPluginManager' => 'Zend\Mvc\Service\ControllerPluginManagerFactory',
//            'ConsoleAdapter'                 => 'Zend\Mvc\Service\ConsoleAdapterFactory',
//            'ConsoleRouter'                  => 'Zend\Mvc\Service\RouterFactory',
//            'ConsoleViewManager'             => 'Zend\Mvc\Service\ConsoleViewManagerFactory',
//            'DependencyInjector'             => 'Zend\Mvc\Service\DiFactory',
//            'DiAbstractServiceFactory'       => 'Zend\Mvc\Service\DiAbstractServiceFactoryFactory',
//            'DiServiceInitializer'           => 'Zend\Mvc\Service\DiServiceInitializerFactory',
//            'DiStrictAbstractServiceFactory' => 'Zend\Mvc\Service\DiStrictAbstractServiceFactoryFactory',
            'FilterManager'           => 'Zend\Mvc\Service\FilterManagerFactory',
            'FormElementManager'      => 'Zend\Mvc\Service\FormElementManagerFactory',
//            'HttpRouter'                     => 'Zend\Mvc\Service\RouterFactory',
//            'HttpViewManager'                => 'Zend\Mvc\Service\HttpViewManagerFactory',
//            'HydratorManager'                => 'Zend\Mvc\Service\HydratorManagerFactory',
            'InputFilterManager'      => 'Zend\Mvc\Service\InputFilterManagerFactory',
//            'LogProcessorManager'            => 'Zend\Mvc\Service\LogProcessorManagerFactory',
//            'LogWriterManager'               => 'Zend\Mvc\Service\LogWriterManagerFactory',
            'MvcTranslator'           => 'Zend\Mvc\Service\TranslatorServiceFactory',
            'PaginatorPluginManager'  => 'Zend\Mvc\Service\PaginatorPluginManagerFactory',

//            'Router'                         => 'Zend\Mvc\Service\RouterFactory',
//            'RoutePluginManager'             => 'Zend\Mvc\Service\RoutePluginManagerFactory',
//            'SerializerAdapterManager'       => 'Zend\Mvc\Service\SerializerAdapterPluginManagerFactory',
            'ValidatorManager'        => 'Zend\Mvc\Service\ValidatorManagerFactory',
//            'ViewHelperManager'              => 'Zend\Mvc\Service\ViewHelperManagerFactory',
//            'ViewFeedRenderer'               => 'Zend\Mvc\Service\ViewFeedRendererFactory',
//            'ViewFeedStrategy'               => 'Zend\Mvc\Service\ViewFeedStrategyFactory',
//            'ViewJsonRenderer'               => 'Zend\Mvc\Service\ViewJsonRendererFactory',
//            'ViewJsonStrategy'               => 'Zend\Mvc\Service\ViewJsonStrategyFactory',
//            'ViewManager'                    => 'Zend\Mvc\Service\ViewManagerFactory',
//            'ViewResolver'                   => 'Zend\Mvc\Service\ViewResolverFactory',
//            'ViewTemplateMapResolver'        => 'Zend\Mvc\Service\ViewTemplateMapResolverFactory',
//            'ViewTemplatePathStack'          => 'Zend\Mvc\Service\ViewTemplatePathStackFactory',
        ),
        'aliases'            => array(
            'Configuration'                     => 'Config',
//            'Console'                                => 'ConsoleAdapter',
//            'Di'                                     => 'DependencyInjector',
//            'Zend\Di\LocatorInterface'               => 'DependencyInjector',
            'Zend\Mvc\Controller\PluginManager' => 'ControllerPluginManager',
//            'Zend\View\Resolver\TemplateMapResolver' => 'ViewTemplateMapResolver',
//            'Zend\View\Resolver\TemplatePathStack'   => 'ViewTemplatePathStack',
//            'Zend\View\Resolver\AggregateResolver'   => 'ViewResolver',
//            'Zend\View\Resolver\ResolverInterface'   => 'ViewResolver',
            'ControllerManager'                 => 'ControllerLoader'
        ),
        'abstract_factories' => array(//            'Zend\Form\FormAbstractServiceFactory',
        ),
    );

}
