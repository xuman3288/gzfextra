<?php

namespace Gzfextra\Validator;

use Zend\Validator\AbstractValidator;


/**
 * Class Mobile
 *
 * @author  moln.xie@gmail.com
 */
class Mobile extends AbstractValidator
{
    const INVALID_MOBILE = 'invalidMobile';

    protected $messageTemplates = array(
        self::INVALID_MOBILE => '手机号码格式不正确',
    );

    public function isValid($value)
    {
        if (!preg_match('/^1\d{10}$/', $value)) {
            $this->error(self::INVALID_MOBILE);
            return false;
        }

        return true;
    }
} 