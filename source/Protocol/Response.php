<?php
/**
 * @author Iris Xie
 * Create: 2017-05-30
 */
namespace Iris\NsqToSwoole\Protocol;

use Iris\NsqToSwoole\Exception\FrameException;

class Response {
    /**
     * Frame types
     */
    const FRAME_TYPE_RESPONSE = 0;
    const FRAME_TYPE_ERROR = 1;
    const FRAME_TYPE_MESSAGE = 2;

    /**
     * Heartbeat response content
     */
    const HEARTBEAT = '_heartbeat_';

    /**
     * OK response content
     */
    const OK = 'OK';

    /**
     * Read frame
     *
     * @param string $buffer
     * @throws FrameException
     * @return array With keys: type, data
     */
    public static function readFrame($buffer) {
        $frame = [
            'size' => self::readInt(substr($buffer, 0, 4)),
            'type' => self::readInt(substr($buffer, 4, 4))
        ];

        switch ($frame['type']) {
            case self::FRAME_TYPE_RESPONSE:
                $frame['response'] = substr($buffer, 8);
                break;

            case self::FRAME_TYPE_ERROR:
                $frame['error'] = substr($buffer, 8);
                break;

            case self::FRAME_TYPE_MESSAGE:
                $frame['ts'] = self::readLong(substr($buffer, 8, 8));
                $frame['attempts'] = self::readShort(substr($buffer, 16, 2));
                $frame['id'] = substr($buffer, 18, 16);
                $frame['payload'] = substr($buffer, 34);
                break;

            default:
                throw new FrameException(substr($buffer, 8));
                break;
        }

        return $frame;
    }

    /**
     * Test if frame is a message frame
     *
     * @param array $frame
     * @return boolean
     */
    public static function isMessage(array $frame) {
        return isset($frame['type'], $frame['payload']) && $frame['type'] === self::FRAME_TYPE_MESSAGE;
    }

    /**
     * Test if frame is heartbeat
     *
     * @param array $frame
     *
     * @return boolean
     */
    public static function isHeartbeat(array $frame) {
        return isset($frame['type'], $frame['response']) && $frame['type'] === self::FRAME_TYPE_RESPONSE
            && $frame['response'] === self::HEARTBEAT;
    }

    /**
     * Test if frame is OK
     *
     * @param array $frame
     * @return boolean
     */
    public static function isOK(array $frame) {
        return isset($frame['type'], $frame['response']) && $frame['type'] === self::FRAME_TYPE_RESPONSE
            && $frame['response'] === self::OK;
    }

    /**
     * Read and unpack short integer (2 bytes) from connection
     *
     * @param string $section
     * @return int
     */
    private static function readShort($section) {
        list(,$res) = unpack('n', $section);

        return $res;
    }

    /**
     * Read and unpack integer (4 bytes)
     *
     * @param string $section
     * @return int
     */
    private static function readInt($section) {
        list(,$size) = unpack('N', $section);
        if ((PHP_INT_SIZE !== 4)) {
            $size = sprintf("%u", $size);
        }

        return (int)$size;
    }

    /**
     * Read and unpack long (8 bytes)
     *
     * @param string $section
     * @return string
     */
    private static function readLong($section) {
        $hi = unpack('N', substr($section, 0, 4));
        $lo = unpack('N', substr($section, 4, 4));

        // workaround signed/unsigned braindamage in php
        $hi = sprintf("%u", $hi[1]);
        $lo = sprintf("%u", $lo[1]);

        return bcadd(bcmul($hi, "4294967296"), $lo);
    }
}
