<?php

namespace Gzfextra\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\Params as ZendParams;

/**
 * Class Params
 *
 * @author  moln.xie@gmail.com
 */
class Params extends ZendParams
{
    public function __invoke($param = null, $default = null)
    {
        if ($param === null) {
            return $this;
        }

        /** @var \Zend\Http\PhpEnvironment\Request $request */
        $request = $this->getController()->getRequest();
        if (($value = $this->fromRoute($param)) !== null) {
            return $value;
        } else if (($value = $request->getPost($param)) !== null) {
            return $value;
        } else if (($value = $request->getQuery($param)) !== null) {
            return $value;
        }

        return $value === null ? $default : $value;
    }
} 