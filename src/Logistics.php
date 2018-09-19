<?php

namespace Careywong\Logistics;

use Careywong\Logistics\Exceptions\InvalidArgumentException;

class Logistics
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
    }
}
