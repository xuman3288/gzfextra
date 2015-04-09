<?php

namespace Gzfextra\FileStorage\Adapter;

/**
 * Class FileSystem
 *
 * @author  moln.xie@gmail.com
 */
class FileSystem extends AbstractStorageAdapter
{
    protected $defaultPath = '/tmp';

    public function move($source, $target)
    {
        return move_uploaded_file($source, $target);
    }

    /**
     * @param      $directory
     * @param bool $showDetail
     *
     * @return array|FileInfo[]
     */
    public function readDirectory($directory, $showDetail = false)
    {
        $list = glob($this->getDefaultPath() . trim($directory, '\\/') . '/*');
        if ($showDetail) {
            foreach ($list as &$file) {
                $file = new FileInfo(
                    array(
                        'name' => basename($file),
                        'size' => filesize($file),
                        'mtime' => filemtime($file),
                        'type' => is_dir($file) ? 'directory' : 'file',
                    )
                );
            }

            return $list;
        } else {
            return $list;
        }
    }

    public function mkdirs($path, $mode = 0777)
    {
        $path = $this->getDefaultPath() .
            str_replace(
                array('/', '\\'),
                DIRECTORY_SEPARATOR,
                trim(str_replace(array('./', '../'), '', $path), '\\/')
            );

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

    public function upload($value)
    {
        $current = getcwd();
        chdir($this->getDefaultPath());
        $value = $this->getFilterChain()->filter($value);
        chdir($current);
        return $value;
    }

    /**
     * Delete directory or file
     *
     * @param string $path
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function delete($path)
    {
        $path = realpath($path);
        if (strpos($path, $this->getDefaultPath()) === 0) {
            if (is_dir($path)) {
                foreach (glob($path . '/*') as $file) {
                    $this->delete($path);
                }
                return @rmdir($path);
            } else {
                return @unlink($path);
            }
        } else {
            throw new \InvalidArgumentException("Path not allowed '$path'");
        }
    }

    /**
     * @param string $defaultPath
     *
     * @throws \InvalidArgumentException
     * @return AbstractStorageAdapter
     */
    public function setDefaultPath($defaultPath)
    {
        $defaultPath = realpath($defaultPath);
        if (!$defaultPath) {
            throw new \InvalidArgumentException('Error default path!');
        }
        $this->defaultPath = $defaultPath . DIRECTORY_SEPARATOR;
        return $this;
    }
}