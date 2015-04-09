<?php
namespace Gzfextra\FileStorage\Adapter;

use Gzfextra\FileStorage\Client\Ftp as FtpClient;
use Gzfextra\FileStorage\Filter\RenameUpload;

/**
 * Class Ftp
 *
 * @author  moln.xie@gmail.com
 */
class Ftp extends AbstractStorageAdapter
{
    protected $ftp;

    public function __construct(array $config)
    {
        if (empty($config['ftp'])) {
            throw new \InvalidArgumentException('Unknown ftp config.');
        }

        $this->ftp = new FtpClient($config['ftp']);
        parent::__construct($config);
    }

    public function move($source, $target)
    {
        $this->ftp->chdir($this->getDefaultPath());
        return $this->ftp->upload($source, $target);
    }

    /**
     * @param      $directory
     * @param bool $showDetail
     *
     * @return array|FileInfo[]
     */
    public function readDirectory($directory, $showDetail = false)
    {
        $this->ftp->chdir($this->getDefaultPath());
        if ($showDetail) {
            $list = $this->ftp->rawlist($directory);
            foreach ($list as &$file) {
                $file = new FileInfo($file);
            }

            return $list;
        } else {
            return $this->ftp->nlist($directory);
        }
    }

    public function upload($value)
    {
        if (!(is_array($value) && isset($value['tmp_name']))) {
            throw new \InvalidArgumentException('Invalid upload parameter.');
        }
        /** @var \Gzfextra\FileStorage\Filter\RenameUpload $filter */
        $filter = $this->getFilter('renameupload');
        $filter->setUploadCallback(function ($sourceFile, $targetFile) use ($filter, $value) {
            $this->ftp->chdir($this->getDefaultPath());
            $dirname = dirname($targetFile);
            $this->ftp->mkdirs($dirname, $filter->getMkdirMode());
            if (!$this->ftp->upload($sourceFile, $targetFile)) {
                throw new \RuntimeException('Ftp upload error: ' . $this->ftp->getErrorMessage());
            }
        });

        return $this->getFilterChain()->filter($value);
    }

    public function __destruct()
    {
        $this->ftp->close();
    }

    public function mkdirs($path, $mode = 0777)
    {
        $this->ftp->chdir($this->getDefaultPath());
        return $this->ftp->mkdirs($path, $mode);
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
        $this->ftp->chdir($this->getDefaultPath());
        $result = $this->ftp->delete($path);
        if (!$result) {
            $files = $this->ftp->nlist($path);
            if ($files && is_array($files)) {
                foreach ($files as $file) {
                    $this->delete($file);
                }

                return $this->ftp->rmdir($path);
            }

            return false;
        }
    }
}