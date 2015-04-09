<?php
namespace Gzfextra\FileStorage\Filter;

use Zend\Filter\StringToLower;

/**
 * Class LowerCaseName
 *
 * @author  Moln Xie
 */
class LowerCaseName extends StringToLower
{
    public function filter($value)
    {
        if (is_array($value) && isset($value['name'])) {
            $value['name'] = parent::filter($value['name']);
        } else if (is_string($value)) {
            $value = parent::filter($value);
        } else {
            throw new \InvalidArgumentException('Error argument type "' . gettype($value) . '"');
        }

        return $value;
    }
}