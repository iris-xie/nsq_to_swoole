<?php
/**
 * @author Iris Xie
 * Create: 2017-05-30
 */
namespace Iris\NsqToSwoole\Monitor;

use Iris\NsqToSwoole\Exception\ConnectionException;
use Iris\NsqToSwoole\Exception\FrameException;
use Iris\NsqToSwoole\Protocol\Command;
use Iris\NsqToSwoole\Protocol\Message;
use Iris\NsqToSwoole\Protocol\Response;

class Consumer extends AbstractMonitor {
    /**
     * Subscribe topic
     *
     * @var string
     */
    protected $topic;

    /**
     * Subscribe channel
     *
     * @var string
     */
    protected $channel;

    /**
     * Subscribe callback
     *
     * @var callable
     */
    protected $callback;

    /**
     * @param string $topic
     * @param string $channel
     * @param callable $callback
     */
    public function initSubscribe($topic, $channel, $callback) {
        $this->topic = $topic;
        $this->channel = $channel;
        $this->callback = $callback;
    }

    /**
     * Get swoole async client
     *
     * @return \swoole_client
     */
    public function getMonitor() {
        if ($this->monitor === null) {
            $this->monitor = new \swoole_client(SWOOLE_TCP, SWOOLE_SOCK_ASYNC);

            $this->monitor->set($this->setting);

            $this->monitor->on('connect', [$this, 'monitorOnConnect']);
            $this->monitor->on('receive', [$this, 'receiveAndDispatchMessage']);
            $this->monitor->on('error', [$this, 'monitorOnError']);
            $this->monitor->on('close', [$this, 'monitorOnClose']);

            $this->monitor->connect($this->host, $this->port, $this->timeout);
        }

        return $this->monitor;
    }

    /**
     * Receive data from nsq and then dispatch callback for async sub loop
     *
     * @param \swoole_client $monitor
     * @param string $data
     */
    public function receiveAndDispatchMessage(\swoole_client $monitor, $data) {
        $frame = Response::readFrame($data);

        // intercept errors/responses
        if (Response::isHeartbeat($frame)) {
            $monitor->send(Command::nop());
        } elseif (Response::isMessage($frame)) {
            $msg = new Message($frame);

            if (!isset($this->callback) || !is_callable($this->callback)) {
                throw new \BadMethodCallException('Subscribe callback is not callable');
            }

            call_user_func($this->callback, $monitor, $msg);

            // mark as done; get next on the way
            $monitor->send(Command::fin($msg->getId()));
            $monitor->send(Command::rdy(1));

        } elseif (Response::isOK($frame)) {
            //ignore
        } else {
            throw new FrameException('Error/unexpected frame received: ' . json_encode($frame));
        }
    }

    /**
     * Connected
     *
     * @param \swoole_client $monitor
     */
    public function monitorOnConnect(\swoole_client $monitor) {
        $monitor->send(Command::magic());

        //subscribe
        if (!isset($this->topic) || !isset($this->channel)) {
            throw new \InvalidArgumentException('Cannot subscribe without topic or channel');
        }

        $monitor->send(Command::sub($this->topic, $this->channel));
        $monitor->send(Command::rdy(1));
    }

    /**
     * Connect failed
     *
     * @throws ConnectionException
     */
    public function monitorOnError() {
        throw new ConnectionException('Failed to connect to ' . $this->getDomain());
    }

    /**
     * Connect closed
     *
     * @param \swoole_client $monitor
     */
    public function monitorOnClose(\swoole_client $monitor) {
        //reconnect
        $monitor->connect($this->host, $this->port, $this->timeout);
    }
}
