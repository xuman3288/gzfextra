<?php

namespace Gzfextra\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\View\Helper\AbstractHelper;

/**
 * Class Uri
 *
 * @author  Moln Xie
 */
class Uri extends AbstractHelper implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    public function __invoke()
    {
        return $this->serviceLocator->getServiceLocator()->get('request')->getUri();
    }
}