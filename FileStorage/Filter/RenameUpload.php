<?php

namespace Gzfextra\FileStorage\Filter;

use Zend\Filter\File\RenameUpload as ZendRenameUpload;

/**
 * Class RenameUpload
 *
 * @author  Moln Xie
 */
class RenameUpload extends ZendRenameUpload
{
    protected $autoMkdirs = true;

    protected $mkdirMode = 0777;

    protected $uploadCallback;

    /**
     * @return int
     */
    public function getMkdirMode()
    {
        return $this->mkdirMode;
    }

    /**
     * @param int $mkdirMode
     * @return $this
     */
    public function setMkdirMode($mkdirMode)
    {
        $this->mkdirMode = $mkdirMode;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isAutoMkdirs()
    {
        return $this->autoMkdirs;
    }

    /**
     * @param boolean $autoMkdirs
     * @return $this
     */
    public function setAutoMkdirs($autoMkdirs)
    {
        $this->autoMkdirs = $autoMkdirs;
        return $this;
    }

    /**
     * @param callable $uploadCallback
     * @return $this
     */
    public function setUploadCallback(callable $uploadCallback)
    {
        $this->uploadCallback = $uploadCallback;
        return $this;
    }

    protected function uploadCallback($sourceFile, $targetFile)
    {
        if (!$this->uploadCallback) {
            //Default
            $this->isAutoMkdirs() && $this->mkdirs(dirname($targetFile), $this->getMkdirMode());
            $this->checkFileExists($targetFile);
            $this->moveUploadedFile($sourceFile, $targetFile);
        } else {
            $call = $this->uploadCallback;
            $call($sourceFile, $targetFile);
        }
    }

    public function getFinalTarget($uploadData)
    {
        return parent::getFinalTarget($uploadData);
    }

    public function mkdirs($path, $mode = 0777)
    {
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, trim($path, '\\/'));
        $root = '';

        //Windows
        if (strpos($path, ':' . DIRECTORY_SEPARATOR)) {
            list($root, $path) = explode(DIRECTORY_SEPARATOR, $path, 2);
        }

        $paths = explode(DIRECTORY_SEPARATOR, $path);
        foreach ($paths as $dir) {
            $root .= '/' . $dir;
            if (!is_dir($root) && !mkdir($root, $mode)) {
                return false;
            }
        }
        return true;
    }


    public function filter($value)
    {
        if (!is_scalar($value) && !is_array($value)) {
            return $value;
        }

        // An uploaded file? Retrieve the 'tmp_name'
        $isFileUpload = false;
        if (is_array($value)) {
            if (!isset($value['tmp_name'])) {
                return $value;
            }

            $isFileUpload = true;
            $uploadData   = $value;
            $sourceFile   = $value['tmp_name'];
        } else {
            $uploadData = array(
                'tmp_name' => $value,
                'name'     => $value,
            );
            $sourceFile = $value;
        }

        if (isset($this->alreadyFiltered[$sourceFile])) {
            return $this->alreadyFiltered[$sourceFile];
        }

        $targetFile = $this->getFinalTarget($uploadData);
        if (!file_exists($sourceFile) || $sourceFile == $targetFile) {
            return $value;
        }

        $this->uploadCallback($sourceFile, $targetFile);

        $return = $targetFile;
        if ($isFileUpload) {
            $return             = $uploadData;
            $return['tmp_name'] = $targetFile;
        }

        $this->alreadyFiltered[$sourceFile] = $return;

        return $return;
    }
}