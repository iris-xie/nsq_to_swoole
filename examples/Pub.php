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
