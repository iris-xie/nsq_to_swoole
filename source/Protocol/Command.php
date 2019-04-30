<?php
/**
 * @author Iris Xie
 * Create: 2017-05-30
 */
namespace Iris\NsqToSwoole\Protocol;

class Command {
    const MAGIC_V2 = "  V2";

    const IDENTIFY = "IDENTIFY";
    const PING = "PING";
    const SUB = "SUB";
    const PUB = "PUB";
    const MPUB = "MPUB";
    const RDY = "RDY";
    const FIN = "FIN";
    const REQ = "REQ";
    const TOUCH = "TOUCH";
    const CLS = "CLS";
    const NOP = "NOP";
    const AUTH = "AUTH";

    /**
     * "Magic" identifier - for version we support
     *
     * @return string
     */
    public static function magic() {
        return self::MAGIC_V2;
    }

    /**
     * Update client metadata on the server and negotiate features
     *
     * @param array $config
     * @return string
     */
    public static function identify(array $config) {
        return self::packet(self::IDENTIFY, null, json_encode($config));
    }

    /**
     * Liveness
     *
     * @return string
     */
    public static function ping() {
        return self::packet(self::PING);
    }

    /**
     * Subscribe to a topic/channel
     *
     * @param string $topic
     * @param string $channel
     * @return string
     */
    public static function sub($topic, $channel) {
        return self::packet(self::SUB, [$topic, $channel]);
    }

    /**
     * Publish a message to a topic
     *
     * @param string $topic
     * @param string $data
     * @return string
     */
    public static function pub($topic, $data) {
        return self::packet(self::PUB, $topic, $data);
    }

    /**
     * Publish multiple messages to a topic - atomically
     *
     * @param string $topic
     * @param array $data
     * @return string
     */
    public static function mpub($topic, array $data) {
        $msgs = '';
        foreach ($data as $value) {
            $msgs .= pack("N", strlen($value)) . $value;
        }

        return sprintf("%s %s\n%s%s%s", self::MPUB, $topic, pack("N", strlen($msgs)), pack("N", count($data)), $msgs);
    }

    /**
     * Update RDY state - indicate you are ready to receive N messages
     *
     * @param int $count
     * @return string
     */
    public static function rdy($count) {
        return self::packet(self::RDY, $count);
    }

    /**
     * Finish a message
     *
     * @param string $message_id
     * @return string
     */
    public static function fin($message_id) {
        return self::packet(self::FIN, $message_id);
    }

    /**
     * Re-queue a message
     *
     * @param string $message_id
     * @param int $timeout In microseconds
     * @return string
     */
    public static function req($message_id, $timeout) {
        return self::packet(self::REQ, [$message_id, $timeout]);
    }

    /**
     * Reset the timeout for an in-flight message
     *
     * @param string $message_id
     * @return string
     */
    public static function touch($message_id) {
        return self::packet(self::TOUCH, $message_id);
    }

    /**
     * Cleanly close
     *
     * @return string
     */
    public static function cls() {
        return self::packet(self::CLS);
    }

    /**
     * No-op
     *
     * @return string
     */
    public static function nop() {
        return self::packet(self::NOP);
    }

    /**
     * Auth for server
     *
     * @param string $password
     * @return string
     */
    public static function auth($password) {
        return self::packet(self::AUTH, null, $password);
    }

    /**
     * Pack string
     *
     * @param string $cmd
     * @param mixed $params
     * @param mixed $data
     * @return string
     */
    private static function packet($cmd, $params = null, $data = null) {
        if (is_array($params)) {
            $params = implode(' ', $params);
        }

        if ($data !== null) {
            $data = pack('N', strlen($data)) . $data;
        }

        return sprintf("%s %s\n%s", $cmd, $params, $data);
    }
}
