<h1 align="center"> logistics </h1>

<p align="center"> A set of logistics service.</p>

## 支持快递渠道
shipmentId | 快递名称
:---:|:---:
1  | 顺丰速运
2  | 百世快递
3  | 中通快递
4  | 申通快递
5  | 圆通速递
6  | 韵达速递
7  | 邮政快递包裹
8  | EMS
9  | 天天快递
10 | 京东快递
11 | 优速快递
12 | 德邦快递
13 | 宅急送
14 | TNT快递
15 | UPS
16 | DHL
17 | FEDEX
18 | 国际e邮宝
19 | 全峰快递
20 | 国通
21 | 安能物流

## 安装

```shell
composer require careywong/logistics -vvv
```

## 使用
### 快递鸟物流服务
```php
use Erp\Logistics\Logistics;
$logistics = new Logistics();

$service = $logistics->getInstance('KDNiao');
$service->setConfig($EBusinessID, $AppKey);

# 获取快递单所属快递公司信息
$service->getShipper($trackingNumber);

# 获取快递轨迹
$service->getTrace($shipmentId, $trackingNumber);
```

### Trackingmore 物流服务
```php
use Erp\Logistics\Logistics;
$logistics = new Logistics();

$service = $logistics->getInstance('Trackingmore');
$service->setConfig($ApiKey);

# 获取快递单所属快递公司信息
$service->getShipper($trackingNumber);

# 获取快递轨迹
$service->getTrace($shipmentId, $trackingNumber);
```

## 返回值
### 数据类型
json

### 数据格式( json 用数组展示)
```php
$formatTrace = [
    'success' => '',
    'message' => '',
    'trackingNumber' => '123456789',
    'trackinfo' => [ // 按时间顺序排序
            0 => [
              "time" => "2018-06-30 00:46:30",
              "event" => " XXX 已揽收",
            ],
            1 => [
              "time" => "2018-06-30 03:41:06",
              "event" => "已发往 XXX ",
            ],
            ...
            6 => [
                  "time" => "2018-07-01 18:26:59",
                  "event" => " XXX 已签收",
            ],
    ],
    'lastEvent' => ' XXX 已签收',
    'lastUpdateTime' => '2018-07-01 18:26:59',
    'packageStatus' => '2', // 0: 无轨迹，1：在途，2：已签收，3：异常
];
```

## License

MIT
