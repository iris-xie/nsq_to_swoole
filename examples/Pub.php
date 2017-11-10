<?php
/**
 * Publish test
 *
 * @author Iris Xie
 * @create 2017-06-01
 */
require __DIR__ . '/../autoload.php';

$hosts = [
    ['host' => '192.168.9.135', 'port' => 4150],
];

$client = new Iris\NsqToSwoole\Client;
$client->setTarget($hosts, 1);

$client->pub('examples', 'Hello From iris-xie', 1);

$http_config = [
   '_host'=>'',
    '_port'=>'',
    '_topic'=>'',
    '_nch' => null,			//nsq client handle
    '_retryTimes' => 1,		//重试次数
    '_connectionTimeout' => 3,
    '_readWriteTimeout' => 3,	//读写时长  单位：秒
];

$client = new Iris\NsqToSwoole\HttpClient($http_config);

$msg= [
    'msg1',
    'msg2',
    'msg3'
];

$client->mpub($msg);
