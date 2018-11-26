<?php
/**
 * Created by PhpStorm.
 * User: cwang
 * Date: 2018/9/19
 * Time: 13:51.
 */

namespace Erp\Logistics;

use Erp\Logistics\Exceptions\HttpException;
use Erp\Logistics\Exceptions\InvalidArgumentException;

class KDNiao implements Service
{
    private $ReqURL = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';
    protected $EBusinessID;
    protected $AppKey;
    protected $RequestType;
    protected $shipperCodeMap = [
        1  => 'SF',
        2  => 'HTKY',
        3  => 'ZTO',
        4  => 'STO',
        5  => 'YTO',
        6  => 'YD',
        7  => 'YZPY',
        8  => 'EMS',
        9  => 'HHTT',
        10 => 'JD',
        11 => 'UC',
        12 => 'DBL',
        13 => 'ZJS',
        14 => 'TNT',
        15 => 'UPS',
        16 => 'DHL',
        17 => 'FEDEX',
        18 => 'GJEYB',
        19 => '',
        20 => 'GTO',
        21 => 'ANE',
    ];

    public function setConfig($EBusinessID, $AppKey)
    {
        $this->EBusinessID = $EBusinessID;
        $this->AppKey = $AppKey;
    }

    /**
     * 获取快递物流轨迹(实现接口).
     *
     * @param $shipmentId
     * @param $trackingNumber
     *
     * @throws HttpException
     * @throws InvalidArgumentException
     *
     * @return mixed|void
     */
    public function getTrace($shipmentId, $trackingNumber)
    {
        $shipperCode = $this->shipperCodeMap[$shipmentId];
        if (empty($shipperCode)) {
            throw new InvalidArgumentException('目前暂不支持该快递。');
        }

        $trace = $this->_getTrace($shipperCode, $trackingNumber);

        return $this->formatTrace($trace);
    }

    /**
     * 获取快递单号所属快递公司(实现接口).
     *
     * @param $trackingNumber
     *
     * @return mixed|void
     */
    public function getShipper($trackingNumber)
    {
        $formatShipper = [
            'success'  => false,
            'message'  => '',
            'shippers' => [],
        ];

        $shipper = $this->getShipperCode($trackingNumber);

        if ($shipper['Success'] == 'Success') {
            $formatShipper['success'] = true;
        }
        if (!empty($shipper['Shippers']) && is_array($shipper['Shippers'])) {
            foreach ($shipper['Shippers'] as $key => $item) {
                $formatShipper['shippers'][$key]['shipperName'] = $item['ShipperName'];
                $formatShipper['shippers'][$key]['shipmentId'] = array_flip($this->shipperCodeMap)[$item['ShipperCode']];
            }
        }

        return json_encode($formatShipper);
    }

    public function formatTrace($trace)
    {
        $formatTrace = [
            'success'        => '',
            'message'        => '',
            'trackingNumber' => '',
            'trackinfo'      => [],
            'lastEvent'      => '',
            'lastUpdateTime' => '',
            'packageStatus'  => '', // 0: 无轨迹，1：在途，2：已签收，3：异常
        ];

        if ($trace['Success']) {
            $formatTrace['success'] = true;
        }

        switch ($trace['State']) {
            case '0':
                $formatTrace['packageStatus'] = '0';
                break;
            case '1':
                $formatTrace['packageStatus'] = '1';
                break;
            case '2':
                $formatTrace['packageStatus'] = '1';
                break;
            case '3':
                $formatTrace['packageStatus'] = '2';
                break;
            default:
                $formatTrace['packageStatus'] = '3';
                break;
        }

        $formatTrace['trackingNumber'] = $trace['LogisticCode'];

        if (!empty($trace['Traces']) && is_array($trace['Traces'])) {
            foreach ($trace['Traces'] as $key => $item) {
                $formatTrace['trackinfo'][$key]['time'] = $item['AcceptTime'];
                $formatTrace['trackinfo'][$key]['event'] = $item['AcceptStation'];
            }
            array_multisort($formatTrace['trackinfo'], SORT_NATURAL, array_column($formatTrace['trackinfo'], 'time'));
        }

        $formatTrace['lastEvent'] = end($formatTrace['trackinfo'])['event'];
        $formatTrace['lastUpdateTime'] = end($formatTrace['trackinfo'])['time'];

        // 部分运单拍照签收或其他签收，状态返回值异常
        $signedFlag = ['签收', '代签'];
        foreach ($signedFlag as $flag) {
            if (strpos($formatTrace['lastEvent'], $flag) !== false) {
                $formatTrace['packageStatus'] = '2';
                break;
            }
        }

        return json_encode($formatTrace);
    }

    /**
     * 获取物流轨迹.
     *
     * @param array $request
     *
     * @return
     */
    public function _getTrace($ShipperCode, $LogisticCode)
    {
        $request = [
            'ShipperCode'  => $ShipperCode, //快递公司编码
            'LogisticCode' => $LogisticCode, //快递单号
        ];

        if (empty($this->AppKey) || empty($this->EBusinessID)) {
            throw new InvalidArgumentException('InvalidArgumentException');
        }

        if (empty($request['ShipperCode']) || empty($request['LogisticCode'])) {
            throw new InvalidArgumentException('InvalidArgumentException');
        }

        try {
            $this->RequestType = 1002;

            return $this->getResponse($request);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 获取快递单所属快递公司信息.
     *
     * @param $request
     *
     * @return array
     */
    public function getShipperCode($LogisticCode)
    {
        $request = [
            'LogisticCode' => $LogisticCode, //快递单号
        ];

        if (empty($request)) {
            return [
                'Success' => '0',
                'Reason'  => '请求参数为空',
            ];
        }

        $this->RequestType = 2002;

        try {
            $response = $this->getResponse($request); // 返回值可能为多个快递公司Code，按概率大小排序的
            return $response;
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * 请求接口.
     *
     * @param array $request
     *
     * @return array
     */
    protected function getResponse($request)
    {
        $requestData = json_encode($request);

        $datas = [
            'EBusinessID' => $this->EBusinessID,
            'RequestType' => $this->RequestType,
            'RequestData' => urlencode($requestData),
            'DataType'    => '2',
        ];

        $datas['DataSign'] = $this->encrypt($requestData, $this->AppKey);

        try {
            return json_decode($this->sendPost($this->ReqURL, $datas), true);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * post提交数据.
     *
     * @param string $url   请求Url
     * @param array  $datas 提交的数据
     *
     * @return url响应返回的html
     */
    protected function sendPost($url, $datas)
    {
        $temps = [];
        foreach ($datas as $key => $value) {
            $temps[] = sprintf('%s=%s', $key, $value);
        }
        $post_data = implode('&', $temps);
        $url_info = parse_url($url);
        if (empty($url_info['port'])) {
            $url_info['port'] = 80;
        }
        $httpheader = 'POST '.$url_info['path']." HTTP/1.0\r\n";
        $httpheader .= 'Host:'.$url_info['host']."\r\n";
        $httpheader .= "Content-Type:application/x-www-form-urlencoded\r\n";
        $httpheader .= 'Content-Length:'.strlen($post_data)."\r\n";
        $httpheader .= "Connection:close\r\n\r\n";
        $httpheader .= $post_data;
        $fd = fsockopen($url_info['host'], $url_info['port']);
        fwrite($fd, $httpheader);
        $gets = '';
        $headerFlag = true;

        if (empty($fd)) {
            return $gets;
        }

        while (!feof($fd)) {
            if (($header = @fgets($fd)) && ($header == "\r\n" || $header == "\n")) {
                break;
            }
        }
        while (!feof($fd)) {
            $gets .= fread($fd, 128);
        }
        fclose($fd);

        return $gets;
    }

    /**
     * Sign签名生成.
     *
     * @param data 内容
     * @param appkey Appkey
     *
     * @return DataSign签名
     */
    protected function encrypt($data, $AppKey)
    {
        return urlencode(base64_encode(md5($data.$AppKey)));
    }
}
