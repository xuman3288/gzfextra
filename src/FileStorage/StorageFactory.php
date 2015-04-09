<?php

namespace Gzfextra\FileStorage;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Class StorageFactory
 *
 * @author  Moln Xie
 */
class StorageFactory implements FactoryInterface
{

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Config');
        $config = $config['file_storage'];
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
        $this->setFileAlias($vm, $fm);

        $fm->setInvokableClass('lowercasename', __NAMESPACE__ . '\Filter\LowerCaseName');
        $fm->setInvokableClass('renameupload', __NAMESPACE__ . '\Filter\RenameUpload');

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