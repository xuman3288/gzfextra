<?php
/**
 *
 * User: xiemoln.xie@gmail.com
 * Date: 14-5-12
 * Time: 下午6:07
 */

namespace Gzfextra\Stdlib;


trait OptionsTrait
{

    /**
     * Configure state
     *
     * @param  array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $method = 'set' . str_replace(' ', '', ucwords(str_replace('_', ' ', $key)));
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this;
    }
} 