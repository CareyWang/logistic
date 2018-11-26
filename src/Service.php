<?php
/**
 * Created by PhpStorm.
 * User: cwang
 * Date: 2018/9/19
 * Time: 15:00.
 */

namespace Erp\Logistics;

interface Service
{
    /**
     * 获取物流轨迹.
     *
     * @param $shipmentId
     * @param $trackingNumber
     *
     * @return mixed
     */
    public function getTrace($shipmentId, $trackingNumber);

    /**
     * 获取快递单号所属快递公司，按概率大小排序.
     *
     * @param $trackingNumber
     *
     * @return mixed
     */
    public function getShipper($trackingNumber);

    /**
     * 返回值格式化为json.
     *
     * @param $response
     *
     * @return mixed
     */
    public function formatTrace($trace);
}
