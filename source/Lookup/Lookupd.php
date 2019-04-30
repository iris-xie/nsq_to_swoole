<?php
/**
 * @author Iris Xie
 * Create: 2017-05-31
 */
namespace Iris\NsqToSwoole\Lookup;

use Iris\NsqToSwoole\Contracts\LookupInterface;
use Iris\NsqToSwoole\Exception\LookupException;

/**
 * Represents nsqlookupd and allows us to find machines we need to talk to
 * for a given topic
 */
class Lookupd implements LookupInterface {
    /**
     * Hosts to connect to
     * 
     * @var array
     */
    private $hosts;

    /**
     * Connection timeout, in seconds
     * 
     * @var float
     */
    private $connectionTimeout;

    /**
     * Response timeout, in seconds
     * 
     * @var float
     */
    private $responseTimeout;

    /**
     * Constructor
     * 
     * @param array $hosts Will default to localhost
     * @param float $connectionTimeout
     * @param float $responseTimeout
     */
    public function __construct(array $hosts = null, $connectionTimeout = 1.0, $responseTimeout = 2.0) {
        if ($hosts === null) {
            $this->hosts = [
                ['host' => 'localhost', 'port' => 4161]
            ];
        } else {
            $this->hosts = $hosts;
        }

        $this->connectionTimeout = $connectionTimeout;
        $this->responseTimeout = $responseTimeout;
    }

    /**
     * Lookup hosts for a given topic
     * 
     * @param string $topic
     * @throws LookupException If we cannot talk to / get back invalid response
     *      from nsqlookupd
     * 
     * @return array keys: host、port
     */
    public function lookupHosts($topic) {
        $lookupHosts = [];

        foreach ($this->hosts as $item) {
            $url = sprintf('http://%s:%d/lookup?topic=%s', isset($item['host']) ? $item['host'] : 'localhost',
                isset($item['port']) ? $item['port'] : 4161, urlencode($topic));

            $ch = curl_init($url);
            $options = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => false,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_ENCODING       => '',
                CURLOPT_USERAGENT      => 'nsq swoole client',
                CURLOPT_CONNECTTIMEOUT => $this->connectionTimeout,
                CURLOPT_TIMEOUT        => $this->responseTimeout,
                CURLOPT_FAILONERROR    => true
            ];

            curl_setopt_array($ch, $options);

            if (!$result = curl_exec($ch)) {
                throw new LookupException('Error talking to nsq lookupd via ' . $url);
            }

            curl_close($ch);
            $result = json_decode($result, true);

            if (!isset($result['producers'])) {
                throw new LookupException('Empty producers data');
            }

            $producers = $result['producers'];
            foreach ($producers as $producer) {
                if (isset($producer['address'])) {
                    $address = $producer['address'];
                } else {
                    $address = $producer['broadcast_address'];
                }

                $domain = $address . ':' . $producer['tcp_port'];

                if (!isset($lookupHosts[$domain])) {
                    $lookupHosts[$domain] = [
                        'host' => $address,
                        'port' => $producer['tcp_port']
                    ];
                }
            }
        }

        return $lookupHosts ? array_values($lookupHosts) : $lookupHosts;
    }
}
