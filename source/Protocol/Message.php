<?php
/**
 * @author Iris Xie
 * Create: 2017-05-30
 */
namespace Iris\NsqToSwoole\Protocol;

use Iris\NsqToSwoole\Contracts\MessageInterface;
use Iris\NsqToSwoole\Exception\FrameException;

class Message implements MessageInterface {
    /**
     * Message payload
     *
     * @var string
     */
    private $payload = '';

    /**
     * Message ID
     *
     * @var string
     */
    private $id = null;

    /**
     * How many attempts have been made
     *
     * @var int
     */
    private $attempts = null;

    /**
     * Timestamp - UNIX timestamp in seconds (incl. fractions)
     *
     * @var float
     */
    private $ts = null;

    /**
     * Message constructor.
     *
     * @param array $frame
     */
    public function __construct(array $frame) {
        if (!isset($frame['payload']) || !isset($frame['id']) || !isset($frame['attempts']) || !isset($frame['ts'])) {
            throw new FrameException('Error message frame');
        }

        $this->payload = $frame['payload'];
        $this->id = $frame['id'];
        $this->attempts = $frame['attempts'];
        $this->ts = $frame['ts'];
    }

    /**
     * Get message payload
     *
     * @return string
     */
    public function getPayload() {
        return $this->payload;
    }

    /**
     * Get message ID
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get attempts
     *
     * @return int
     */
    public function getAttempts() {
        return $this->attempts;
    }

    /**
     * Get timestamp
     *
     * @return float
     */
    public function getTimestamp() {
        return $this->ts;
    }
}
