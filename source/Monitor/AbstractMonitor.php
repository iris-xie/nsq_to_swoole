<?php
/**
 * @author Iris Xie
 * Create: 2017-05-30
 */
namespace Iris\NsqToSwoole\Monitor;

use Iris\NsqToSwoole\Contracts\MonitorInterface;
use Iris\NsqToSwoole\Exception\SocketException;

abstract class AbstractMonitor implements MonitorInterface {
    /**
     * nsq host
     *
     * @var string
     */
    protected $host = 'localhost';

    /**
     * nsq port
     *
     * @var int
     */
    protected $port = 4150;

    /**
     * connect、read、write timeout
     *
     * @var int
     */
    protected $timeout = 3;

    /**
     * swoole client setting
     *
     * @var array
     */
    protected $setting = [
        'open_length_check'     => true,
        'package_max_length'    => 2048000,
        'package_length_type'   => 'N',
        'package_length_offset' => 0,
        'package_body_offset'   => 4
    ];

    /**
     * swoole client
     *
     * @var \swoole_client
     */
    protected $monitor;

    /**
     * AbstractMonitor constructor.
     *
     * @param string $host
     * @param int    $port
     * @param int    $timeout
     * @param array  $setting
     */
    public function __construct($host = 'localhost', $port = 4150, $timeout = 3, $setting = []) {
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;

        if ($setting) {
            $this->setting = $setting;
        }
    }

    /**
     * Read from the socket exactly $len bytes
     *
     * @param int $len How many bytes to read
     * @return string
     */
    public function read($len){
        $data = $this->getMonitor()->recv($len);

        if ($data === false) {
            throw new SocketException('Failed to read from ' . $this->getDomain());
        } elseif ($data == '') {
            throw new SocketException('Read 0 bytes from ' . $this->getDomain());
        }

        return $data;
    }

    /**
     * Write to the socket.
     *
     * @param string $buf
     */
    public function write($buf) {
        //NO-OP
    }

    /**
     * Reconnect the socket.
     */
    public function reconnect() {
        //NO-OP
    }

    /**
     * Get nsq domain
     *
     * @return string
     */
    public function getDomain() {
        return $this->host . ':' . $this->port;
    }
}
