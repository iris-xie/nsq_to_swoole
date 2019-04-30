<?php
/**
 * @author Iris Xie
 * Create: 2017-05-29
 */
namespace Iris\NsqToSwoole;

use Iris\NsqToSwoole\Contracts\LookupInterface;
use Iris\NsqToSwoole\Exception\PublishException;
use Iris\NsqToSwoole\Monitor\Consumer;
use Iris\NsqToSwoole\Monitor\Producer;
use Iris\NsqToSwoole\Protocol\Command;
use Iris\NsqToSwoole\Protocol\Response;

class Client {
    const PUB_ONE = 1;
    const PUB_TWO = 2;
    const PUB_QUORUM = 5;

    /**
     * Connect/read/write timeout - in seconds
     *
     * @var float
     */
    private $timeout;

    /**
     * Swoole client setting
     *
     * @var array
     */
    private $setting;

    /**
     * Producer monitor pool
     *
     * @var array
     */
    private $producerPool = [];

    /**
     * Consumer monitor pool
     *
     * @var array
     */
    private $consumerPool = [];

    /**
     * Publish success criteria (how many nodes need to respond)
     *
     * @var int
     */
    private $pubSuccessCount = 1;

    /**
     * Client constructor.
     *
     * @param int   $timeout swoole client connect/recv/send timeout
     * @param array $setting swoole client setting
     */
    public function __construct($timeout = 3, $setting = []) {
        $this->timeout = $timeout;
        $this->setting = $setting;
    }

    /**
     * Subscribe to topic/channel
     *
     * @param LookupInterface $lookup Lookup service for hosts from topic
     * @param string $topic
     * @param string $channel
     * @param callable $callback
     * @return $this
     */
    public function sub(LookupInterface $lookup, $topic, $channel, $callback) {
        $hosts = $lookup->lookupHosts($topic);

        foreach ($hosts as $item) {
            $host = isset($item['host']) ? $item['host'] : 'localhost';
            $port = isset($item['port']) ? $item['port'] : 4150;

            $consumer = new Consumer($host, $port, $this->timeout, $this->setting);
            $consumer->initSubscribe($topic, $channel, $callback);
            $consumer->getMonitor();

            $this->consumerPool[] = $consumer;
        }

        return $this;
    }

    /**
     * setTarget
     *
     * We'll remember these hosts for any subsequent pub() call, so you
     * only need to call this once to publish
     *
     * @param array $hosts keys: host、port
     * @param int $cl Consistency level - basically how many `nsqd`
     *      nodes we need to respond to consider a publish successful
     *      The default value is nsqphp::PUB_ONE
     *
     * @throws \InvalidArgumentException If bad CL provided
     * @throws \InvalidArgumentException If we cannot achieve the desired CL
     *      (eg: if you ask for PUB_TWO but only supply one node)
     *
     * @return $this
     */
    public function setTarget(array $hosts, $cl = self::PUB_ONE) {
        foreach ($hosts as $item) {
            $host = isset($item['host']) ? $item['host'] : 'localhost';
            $port = isset($item['port']) ? $item['port'] : 4150;

            $this->producerPool[] = new Producer($host, $port, $this->timeout, $this->setting);
        }

        $producerPoolSize = count($this->producerPool);

        switch ($cl) {
            case self::PUB_ONE:
            case self::PUB_TWO:
                $this->pubSuccessCount = $cl;
                break;

            case self::PUB_QUORUM:
                $this->pubSuccessCount = ceil($producerPoolSize / 2) + 1;
                break;

            default:
                throw new \InvalidArgumentException('Invalid consistency level');
                break;
        }

        if ($this->pubSuccessCount > $producerPoolSize) {
            throw new \InvalidArgumentException(
                sprintf('Cannot achieve desired consistency level with %s nodes', $producerPoolSize)
            );
        }

        return $this;
    }

    /**
     * Pub message
     *
     * @param string $topic A valid topic name: [.a-zA-Z0-9_-] and 1 < length < 32
     * @param string|array $msgs array: multiple messages
     * @param int $tries  Retry times
     *
     * @throws PublishException If we don't get "OK" back from server
     *      (for the specified number of hosts - as directed by `publishTo`)
     *
     * @return $this
     */
    public function pub($topic, $msgs, $tries = 1) {
        // pick a random
        shuffle($this->producerPool);

        $success = 0;
        $errors = [];
        foreach ($this->producerPool as $producer) {
            try {
                for ($run = 0; $run <= $tries; $run++) {
                    try {
                        $payload = is_array($msgs) ? Command::mpub($topic, $msgs) : Command::pub($topic, $msgs);
                        $producer->write($payload);

                        $frame = Response::readFrame($producer->readAll());
                        while (Response::isHeartbeat($frame)) {
                            $producer->write(Command::nop());
                            $frame = Response::readFrame($producer->readAll());
                        }

                        if (Response::isOK($frame)) {
                            $success++;
                        } else {
                            $errors[] = $frame['error'];
                        }

                        break;
                    } catch (\Exception $e) {
                        if ($run >= $tries) {
                            throw $e;
                        }

                        $producer->reconnect();
                    }
                }
            } catch (\Exception $e) {
                $errors[] = $e->getMessage();
            }

            if ($success >= $this->pubSuccessCount) {
                break;
            }
        }

        if ($success < $this->pubSuccessCount) {
            throw new PublishException(
                sprintf('Failed to publish message; required %s for success, achieved %s. Errors were: %s', $this->pubSuccessCount, $success, implode(', ', $errors))
            );
        }

        return $this;
    }
}
