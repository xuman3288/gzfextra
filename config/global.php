<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
    /*'db' => array(
        'driver'         => 'pdo',
        'dsn'            => 'mysql:host=localhost;dbname=zfskeleton',
        'driver_options' => array(
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
            PDO::ATTR_TIMEOUT            => 10
        ),
        'username'       => 'root',
        'password'       => '111111'
    ),*/
    'db'              => array(
        'adapters' => array(
            'db' => array(
                'driver'         => 'pdo',
                'dsn'            => 'mysql:host=localhost;dbname=zfskeleton',
                'driver_options' => array(
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES \'UTF8\'',
                    PDO::ATTR_TIMEOUT            => 10
                ),
                'username'       => 'root',
                'password'       => '111111'
            )
        )

    ),
    'caches'          => array(
        'Cache\FileTest' => array(
            'adapter' => 'filesystem',
            'options' => array(
                'cache_dir' => 'data/cache/cache_cache_filetest'
            ),
            'plugins' => array('serializer')
        )
    ),
    'service_manager' => array(
        'factories'          => array(
            //'Zend\Db\Adapter\Adapter' => 'Zend\Db\Adapter\AdapterServiceFactory'
        ),
        'aliases'            => array(
            //'db' => 'Zend\Db\Adapter\Adapter'
        ),
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Gzfextra\Db\TableGateway\TableGatewayAbstractServiceFactory',
            'Zend\Db\Adapter\AdapterAbstractServiceFactory'
        )
    )
);
