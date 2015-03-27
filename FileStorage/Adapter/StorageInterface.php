<?php
namespace Gzfextra\FileStorage\Adapter;

interface StorageInterface
{

    /**
     *
     * @param $source
     * @param $target
     *
     * @return bool
     */
    public function move($source, $target);

}