<?php
/**
 * Subscribe test
 *
 * @author Iris Xie
 * @create 2017-06-01
 */
require __DIR__ . '/../autoload.php';

$lookup = new Iris\NsqToSwoole\Lookup\Lookupd([
    ['host' => '192.168.9.135', 'port' => 4161],
]);

$client = new Iris\NsqToSwoole\Client;

$client->sub($lookup, 'examples', 'example', function($moniter, $msg) {
    echo sprintf("READ\t%s\t%s\n", $msg->getId(), $msg->getPayload());
});
