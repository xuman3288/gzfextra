<?php

namespace Gzfextra\Db\TableGateway;

use Zend\Db\TableGateway\TableGateway as ZendTableGateway;
use Zend\ServiceManager\ServiceLocatorInterface;


/**
 * Class AbstractTableGateway
 *
 * @author  moln.xie@gmail.com
 *
 * @method int fetchCount($where)
 * @method \ArrayObject|\Zend\Db\RowGateway\RowGateway find($id)
 * @method \Zend\Db\RowGateway\RowGateway create(array $row = null)
 * @method \Zend\Paginator\Paginator fetchPaginator($where = null)
 * @method int deletePrimary($key)
 *
 */
abstract class AbstractTableGateway extends ZendTableGateway
{
    protected static $tableInstances = array();

    /**
     * @return static
     */
    public static function getInstance()
    {
        $className = get_called_class();
        if (!isset(self::$tableInstances[$className])) {
            $classNameSep = explode('\\', $className);
            $config       = end($classNameSep);

            self::$tableInstances[$className] = self::getServiceLocator()->get($config);
        }

        return self::$tableInstances[$className];
    }

    protected static $serviceLocator;

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     */
    public static function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        self::$serviceLocator = $serviceLocator;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public static function getServiceLocator()
    {
        return self::$serviceLocator;
    }


    /**
     * @param $data
     * @return int
     * @throws \RuntimeException
     */
    public function save(&$data)
    {
        if (is_array($data)) {
            $data = new \ArrayObject($data);
        }
        return parent::save($data);
    }


    /**
     * @return bool
     */
    public static function hasInstance()
    {
        $className = get_called_class();
        if (isset(self::$tableInstances[$className])) {
            return true;
        }

        $classNameSep = explode('\\', $className);
        $config       = end($classNameSep);
        return self::getServiceLocator() && self::getServiceLocator()->has($config);
    }
}