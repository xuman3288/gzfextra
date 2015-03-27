<?php

namespace Gzfextra\Db\TableGateway;

use Zend\Db\Sql\TableIdentifier;
use Zend\Db\TableGateway\TableGateway;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class TableGatewayAbstractServiceFactory
 * @author moln.xie@gmail.com
 */
class TableGatewayAbstractServiceFactory implements AbstractFactoryInterface
{
    private $config;

    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param                         $name
     * @param                         $requestedName
     *
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (substr($name, -5) != 'table') {
            return false;
        }

        if (empty($this->config)) {
            $config = $serviceLocator->get('config');
            $this->config = isset($config['tables']) ? $config['tables'] : [];
        }

        if (!isset($this->config[$requestedName])) {
            return false;
        }

        return true;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->config[$requestedName];
        $dbAdapter = $serviceLocator->get(isset($config['adapter']) ? $config['adapter'] : 'db');

        /** @var \Zend\Db\TableGateway\TableGateway $table */
        if (isset($config['schema'])) {
            $config['table'] = new TableIdentifier($config['table'], $config['schema']);
        }

        $featureSet = new Feature\FeatureSet();
        $featureSet->addFeature(new Feature\CommonCallFeature($config['primary']));

        if (isset($config['invokable'])) {
            if (!class_exists($config['invokable'])) {
                throw new \RuntimeException("Class '{$config['invokable']}' not found ");
            }
            $table = new $config['invokable']($config['table'], $dbAdapter, $featureSet);
        } else {
            $table = new TableGateway($config['table'], $dbAdapter, $featureSet);
        }

        if (isset($config['row'])) {
            if ($config['row'] === true) {
                $config['row'] = 'Zend\Db\RowGateway\RowGateway';
            }

            if (is_string($config['row'])) {
                if (!class_exists($config['row'])) {
                    throw new \RuntimeException("Class '{$config['row']}' not found ");
                }

                $rowGatewayPrototype = new $config['row'](
                    $config['primary'],
                    $config['table'],
                    $dbAdapter, $table->getSql()
                );
            } else if (is_object($config['row'])) {
                $rowGatewayPrototype = $config['row'];
            } else {
                throw new \InvalidArgumentException('Error row argument');
            }

            $table->getResultSetPrototype()->setArrayObjectPrototype($rowGatewayPrototype);
        }

        return $table;
    }
}