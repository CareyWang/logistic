<?php
/**
 * Created by PhpStorm.
 * User: cwang
 * Date: 2018/9/17
 * Time: 19:30
 */

namespace Careywong\Logistic;

use GuzzleHttp\Client;

class Trackingmore
{
    const API_BASE_URL = 'http://api.trackingmore.com/v2/';
    const ROUTE_CARRIERS = 'carriers/';
    const ROUTE_CARRIERS_DETECT = 'carriers/detect';
    const ROUTE_TRACKINGS = 'trackings';
    const ROUTE_LIST_ALL_TRACKINGS = 'trackings/get';
    const ROUTE_CREATE_TRACKING = 'trackings/post';
    const ROUTE_TRACKINGS_BATCH = 'trackings/batch';
    const ROUTE_TRACKINGS_REALTIME = 'trackings/realtime';

    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    protected function _getApiData($route, $method = 'GET', $sendData = array())
    {
        $method = strtoupper($method);
        $requestUrl = self::API_BASE_URL . $route;
        $curlObj = curl_init();
        curl_setopt($curlObj, CURLOPT_URL, $requestUrl);
        if ($method == 'GET') {
            curl_setopt($curlObj, CURLOPT_HTTPGET, true);
        } elseif ($method == 'POST') {
            curl_setopt($curlObj, CURLOPT_POST, true);
        } elseif ($method == 'PUT') {
            curl_setopt($curlObj, CURLOPT_PUT, true);
        } else {
            curl_setopt($curlObj, CURLOPT_CUSTOMREQUEST, $method);
        }

        curl_setopt($curlObj, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($curlObj, CURLOPT_TIMEOUT, 90);

        curl_setopt($curlObj, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlObj, CURLOPT_HEADER, 0);
        $headers = array(
            'Trackingmore-Api-Key: ' . $this->apiKey,
            'Content-Type: application/json',
        );
        if ($sendData) {
            $dataString = json_encode($sendData);
            curl_setopt($curlObj, CURLOPT_POSTFIELDS, $dataString);
            $headers[] = 'Content-Length: ' . strlen($dataString);
        }
        curl_setopt($curlObj, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curlObj);
        curl_close($curlObj);
        unset($curlObj);
        return $response;
    }

    /**
     * List all carriers.
     * @return array|mixed
     */
    public function getCarrierList()
    {
        $returnData = array();
        $requestUrl = self::ROUTE_CARRIERS;
        $result = $this->_getApiData($requestUrl, 'GET');
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * Detect a carrier by tracking code.
     * @param $trackingNumber
     * @return array|mixed
     */
    public function detectCarrier($trackingNumber)
    {
        $returnData = array();
        $requestUrl = self::ROUTE_CARRIERS_DETECT;
        $sendData['tracking_number'] = $trackingNumber;
        $result = $this->_getApiData($requestUrl, 'POST', $sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * List all trackings.
     * @param int $page
     * @param int $limit
     * @param int $createdAtMin
     * @param int $createdAtMax
     * @return array|mixed
     */
    public function getTrackingsList($page = 1, $limit = 100, $createdAtMin = 0, $createdAtMax = 0)
    {
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_LIST_ALL_TRACKINGS;
        $createdAtMax = !empty($createdAtMax) ? $createdAtMax : time();
        $sendData['page'] = $page;
        $sendData['limit'] = $limit;
        $sendData['created_at_min'] = $createdAtMin;
        $sendData['created_at_max'] = $createdAtMax;
        $result = $this->_getApiData($requestUrl, 'GET', $sendData);
        if ($result) {
            $returnData = $result;
        }
        return $returnData;
    }

    /**
     * Create a tracking item.
     * @param $carrierCode
     * @param $trackingNumber
     * @param array $extraInfo
     * @return array|mixed
     */
    public function createTracking($carrierCode, $trackingNumber, $extraInfo = array())
    {
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_CREATE_TRACKING;

        $sendData['tracking_number'] = $trackingNumber;
        $sendData['carrier_code'] = $carrierCode;
        $sendData['title'] = !empty($extraInfo['title']) ? $extraInfo['title'] : null;
        $sendData['customer_name'] = !empty($extraInfo['customer_name']) ? $extraInfo['customer_name'] : null;
        $sendData['customer_email'] = !empty($extraInfo['customer_email']) ? $extraInfo['customer_email'] : null;
        $sendData['order_id'] = !empty($extraInfo['order_id']) ? $extraInfo['order_id'] : null;

        $result = $this->_getApiData($requestUrl, 'POST', $sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * Create multiple trackings.
     * @param $multipleData
     * @return array|mixed
     */
    public function createMultipleTracking($multipleData)
    {
        $returnData = array();
        $sendData = array();
        $requestUrl = self::ROUTE_TRACKINGS_BATCH;
        if (!empty($multipleData)) {
            foreach ($multipleData as $val) {
                $items = array();
                $items['tracking_number'] = !empty($val['tracking_number']) ? $val['tracking_number'] : null;
                $items['carrier_code'] = !empty($val['carrier_code']) ? $val['carrier_code'] : null;
                $items['title'] = !empty($val['title']) ? $val['title'] : null;
                $items['customer_name'] = !empty($val['customer_name']) ? $val['customer_name'] : null;
                $items['customer_email'] = !empty($val['customer_email']) ? $val['customer_email'] : null;
                $items['order_id'] = !empty($val['order_id']) ? $val['order_id'] : null;
                $sendData[] = $items;
            }
        }

        $result = $this->_getApiData($requestUrl, 'POST', $sendData);
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }

    /**
     * Get tracking results of a single tracking.
     * @param $carrierCode
     * @param $trackingNumber
     * @return array|mixed
     */
    public function getSingleTrackingResult($carrierCode, $trackingNumber)
    {
        $returnData = array();
        $requestUrl = self::ROUTE_TRACKINGS.'/'.$carrierCode.'/'.$trackingNumber;
        $result = $this->_getApiData($requestUrl, 'GET');
        if ($result) {
            $returnData = json_decode($result, true);
        }
        return $returnData;
    }
}