# nsq_to_swoole


a strong php client for NSQ using swoole extension

### 必要组件

  - PHP >= 5.5
  - Swoole >= 1.8.6

### 安装

    pecl install swoole

    composer require iris/nsq_to_swoole


### 发布

客户端支持NSQ服务器集群,同时支持单次多条消息发送


```php
$hosts = [
    ['host' => '192.168.9.135', 'port' => 4150],
];

$client = new Iris\NsqToSwoole\Client;
$client->setTarget($hosts, 1);

$client->pub('examples', 'Hello From iris-xie', 1);
```

### 订阅

首先通过lookup获取列表,然后才可以订阅

```php
$lookup = new Iris\NsqToSwoole\Lookup\Lookupd([
    ['host' => '192.168.9.135', 'port' => 4161],
]);

$client = new Iris\NsqToSwoole\Client;

$client->sub($lookup, 'examples', 'example', function($moniter, $msg) {
    echo sprintf("READ\t%s\t%s\n", $msg->getId(), $msg->getPayload());
});
```


### 使用示例

    cd nsq_to_swoole/examples

Pub:

    php Pub.php

Sub:

    php Sub.php
