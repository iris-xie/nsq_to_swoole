<?php
/**
 * @author Iris Xie
 * Create: 2017-05-30
 */
namespace Iris\NsqToSwoole\Contracts;

interface MonitorInterface {
    /**
     * Get swoole client
     *
     * @return \swoole_client
     */
    public function getMonitor();

    /**
     * Read from the socket exactly $len bytes
     *
     * @param int $len How many bytes to read
     * @return string
     */
    public function read($len);

    /**
     * Write to the socket.
     *
     * @param string $buf The data to write
     */
    public function write($buf);

    /**
     * Reconnect the socket.
     *
     * @return Resource The socket, after reconnecting
     */
    public function reconnect();
}
