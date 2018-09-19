<?php
/**
 * Created by PhpStorm.
 * User: cwang
 * Date: 2018/9/18
 * Time: 17:59
 */

namespace Careywong\Logistic;

use Careywong\Logistic\KDNiao;
use Careywong\Logistic\Trackingmore;
use Careywong\Logistic\Exceptions\InvalidArgumentException;

class Logistic
{
    const EXISTS_CLASS = [
        'KDNiao',
        'Trackingmore',
    ];

    public function getInstance($className)
    {
        switch ($className) {
            case 'KDNiao':
                return new KDNiao();
                break;
            case 'Trackingmore':
                return new Trackingmore();
                break;
            default:
                throw new InvalidArgumentException("目前不支持 {$className} 渠道");
        }

        return null;
    }
}