<?php

namespace Gzfextra\FileStorage;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;


/**
 * Class StorageAbstractFactory
 * @author Moln xie
 */
class StorageAbstractFactory implements AbstractFactoryInterface
{
    private $configs;

    /**
     * Determine if we can create a service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        if (substr($requestedName, 0, 11) != 'FileStorage') {
            return false;
        }
        if (!($configs = $this->getConfigs($serviceLocator))) {
            return false;
        }

        return isset($configs[$requestedName]);
    }

    private function getConfigs(ServiceLocatorInterface $serviceLocator)
    {
        if ($this->configs !== null) {
            return $this->configs;
        }

        $configs = $serviceLocator->get('config');
        if (isset($configs['file_storage_configs'])) {
            $this->configs = $configs['file_storage_configs'];
        } else {
            $this->configs = false;
        }

        return $this->configs;
    }

    /**
     * Create service with name
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        $config = $this->configs[$requestedName];
        if (!$config['type']) {
            throw new \RuntimeException('Unknown "type" config.');
        }
        if (!$config['options']) {
            throw new \RuntimeException('Unknown "options" config.');
        }

        $className = __NAMESPACE__ . '\\Adapter\\' . ucfirst($config['type']);
        if (!class_exists($className)
            || !in_array(__NAMESPACE__ . '\\Adapter\\StorageInterface', class_implements($className))
        ) {
            throw new \RuntimeException('Error config type:' . $config['type']);
        }

        /** @var Adapter\AbstractStorageAdapter $fileStorage */
        $fileStorage = new $className($config['options']);

        /**
         * @var \Zend\Validator\ValidatorPluginManager $vm
         * @var \Zend\Filter\FilterPluginManager $fm
         */
        $vm = $serviceLocator->get('ValidatorManager');
        $fm = $serviceLocator->get('FilterManager');
        $fm->setInvokableClass('lowercasename', 'Gzfextra\FileStorage\Filter\LowerCaseName');
        $fm->setInvokableClass('renameupload', 'Gzfextra\FileStorage\Filter\RenameUpload');

        $this->setFileAlias($vm, $fm);

        $fileStorage->getValidatorChain()->setPluginManager($vm);
        $fileStorage->getFilterChain()->setPluginManager($fm);

        if (isset($config['options']['validators'])) {
            $fileStorage->addValidators($config['options']['validators']);
        }

        if (isset($config['options']['filters'])) {
            $fileStorage->addFilters($config['options']['filters']);
        }
        return $fileStorage;
    }

    /**
     * @param \Zend\Validator\ValidatorPluginManager $vm
     * @param \Zend\Filter\FilterPluginManager $fm
     */
    private function setFileAlias($vm, $fm)
    {
        $validatorsAliases = array(
            'count'            => 'filecount',
            'crc32'            => 'filecrc32',
            'excludeextension' => 'fileexcludeextension',
            'excludemimetype'  => 'fileexcludemimetype',
            'exists'           => 'fileexists',
            'extension'        => 'fileextension',
            'filessize'        => 'filefilessize',
            'hash'             => 'filehash',
            'imagesize'        => 'fileimagesize',
            'iscompressed'     => 'fileiscompressed',
            'isimage'          => 'fileisimage',
            'md5'              => 'filemd5',
            'mimetype'         => 'filemimetype',
            'notexists'        => 'filenotexists',
            'sha1'             => 'filesha1',
            'size'             => 'filesize',
            'upload'           => 'fileupload',
            'wordcount'        => 'filewordcount',
        );

        $filtersAliases = array(
            'decrypt'   => 'filedecrypt',
            'encrypt'   => 'fileencrypt',
            'lowercase' => 'filelowercase',
            'rename'    => 'filerename',
            'uppercase' => 'fileuppercase',
//            'renameupload' => 'filerenameupload',
        );

        foreach ($validatorsAliases as $key => $value) {
            $vm->setAlias($key, $value);
        }

        foreach ($filtersAliases as $key => $value) {
            $fm->setAlias($key, $value);
        }
    }
}