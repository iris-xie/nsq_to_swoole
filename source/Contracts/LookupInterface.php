<?php
/**
 * @author Iris Xie
 * @create 2017-06-01
 */
namespace Iris\NsqToSwoole\Contracts;

use Iris\NsqToSwoole\Exception\LookupException;

interface LookupInterface {
    /**
     * Lookup hosts for a given topic
     *
     * @param string $topic
     * @throws LookupException If we cannot talk to / get back invalid response
     *      from nsqlookupd
     *
     * @return array keys: host、port
     */
    public function lookupHosts($topic);
}
