<?php
/**
 * Created by PhpStorm.
 * User: cwang
 * Date: 2018/9/19
 * Time: 13:50.
 */

namespace CareyWong\Logistics;

use CareyWong\Logistics\Exceptions\InvalidArgumentException;

class Logistics
{
    const EXISTS_CLASS = [
        'KDNiao',
        'Trackingmore',
    ];
    private $service;

    public function getInstance($className)
    {
        switch ($className) {
            case 'KDNiao':
                $this->service = new KDNiao();
                break;
            case 'Trackingmore':
                $this->service = new Trackingmore();
                break;
            default:
                throw new InvalidArgumentException("目前不支持 {$className} 渠道");
        }

        return $this->service;
    }
}
