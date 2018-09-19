<?php
/**
 * Created by PhpStorm.
 * User: cwang
 * Date: 2018/9/17
 * Time: 19:41.
 */

namespace Careywong\Logistic;

use Careywong\Logistic\Exceptions\HttpException;
use Careywong\Logistic\Exceptions\InvalidArgumentException;

class KDNiao
{
    private $ReqURL = 'http://api.kdniao.cc/Ebusiness/EbusinessOrderHandle.aspx';
    protected $EBusinessID;
    protected $AppKey;
    protected $RequestType;

    public function setConfig($EBusinessID, $AppKey)
    {
        $this->EBusinessID = $EBusinessID;
        $this->AppKey = $AppKey;
    }

    /**
     * 获取物流轨迹.
     *
     * @param array $request
     *
     * @return
     */
    public function getTrace($request)
    {
        $requestSample = [
            'ShipperCode'  => '', //快递公司编码
            'LogisticCode' => '', //快递单号
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
    public function getShipperCode($request)
    {
        if (empty($request)) {
            return json_encode([
                'Success' => '0',
                'Reason'  => '请求参数为空',
            ]);
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
