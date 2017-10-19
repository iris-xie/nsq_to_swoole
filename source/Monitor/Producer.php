<?php
/**
 * @author Iris Xie
 * Create: 2017-05-30
 */
namespace Iris\NsqToSwoole\Monitor;

use Iris\NsqToSwoole\Exception\ConnectionException;
use Iris\NsqToSwoole\Exception\SocketException;
use Iris\NsqToSwoole\Protocol\Command;

class Producer extends AbstractMonitor {
    /**
     * Read all from the socket
     *
     * @return string
     */
    public function readAll() {
        $data = @$this->getMonitor()->recv();

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
        if (@$this->getMonitor()->send($buf) === false) {
            throw new SocketException('Failed to write ' . strlen($buf) . ' bytes to ' . $this->getDomain());
        }
    }

    /**
     * Reconnect the socket.
     *
     * @return \swoole_client
     */
    public function reconnect() {
        if ($this->monitor) {
            $this->monitor->close();
        }

        return $this->getMonitor(true);
    }

    /**
     * Get swoole client
     *
     * @param bool $forceConn Force to connect
     * @return \swoole_client
     * @throws ConnectionException
     */
    public function getMonitor($forceConn = false) {
        if ($this->monitor === null) {
            $this->monitor = new \swoole_client(SWOOLE_TCP);

            $this->monitor->set($this->setting);

            $forceConn = true;
        }

        if ($forceConn) {
            if (!$this->monitor->connect($this->host, $this->port, $this->timeout)) {
                throw new ConnectionException('Failed to connect to ' . $this->getDomain());
            }

            $this->monitor->send(Command::magic());
        }

        return $this->monitor;
    }
}
