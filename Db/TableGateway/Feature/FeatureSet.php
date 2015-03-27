<?php

namespace Gzfextra\Db\TableGateway\Feature;
use Zend\Db\TableGateway\Feature\FeatureSet as ZendFeatureSet;


/**
 * Class CommonCallFeature
 * @author moln.xie@gmail.com
 */
class FeatureSet extends ZendFeatureSet
{
    protected $magicCallFeature;

    /**
     * @param string $method
     * @return bool
     */
    public function canCallMagicCall($method)
    {
        foreach ($this->features as $feature) {
            if (method_exists($feature, $method)) {
                $this->magicCallFeature = $feature;
                return true;
            }
        }
        return false;
    }
    /**
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function callMagicCall($method, $arguments)
    {
        return call_user_func_array(array($this->magicCallFeature, $method), $arguments);
    }
}