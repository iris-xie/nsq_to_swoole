<?php
/**
 * Created by PhpStorm.
 * User: sick
 * Date: 10/11/2017
 * Time: 10:23 AM
 */
/**
 * Nsq Http 客户端
 * @author xielei
 * @create 2016-04-22
 */
namespace Iris\NsqToSwoole;
use GuzzleHttp\Client;
class HttpClient {
    private $_host;
    private $_port;
    private $_topic;
    private $_nch = null;			//nsq client handle
    private $_retryTimes = 1;		//重试次数
    private $_connectionTimeout = 3;
    private $_readWriteTimeout = 3;	//读写时长  单位：秒
    private static $_instances = array();
    private $config;
    public function __construct(array $config) {
        $this->getHttpClient($config);		//获取http client实例
    }
    /**
     * 获取nsq client单例
     * @param string $module	nsq服务模块key
     * @return mixed
     * @throws \Exception
     */
    public static function getInstance() {
        try {
            if (empty($module)) {
                throw new \InvalidArgumentException('NsqHttpClient: module key can not be empty');
            }
            if ( ! isset(self::$_instances)) {
                self::$_instances = new self();
            }
        } catch(\Exception $e) {

            throw $e;
        }
        return self::$_instances;
    }
    /**
     * 一次发布单条nsq消息
     * @param array $nsqData
     * @return bool
     */
    public function pub($nsqData) {
        return $this->doPub('pub', $nsqData);
    }
    /**
     * 一次发布多条nsq消息
     * @param array $nsqDatas
     * @return bool
     */
    public function mpub($nsqDatas) {
        return $this->doPub('mpub', $nsqDatas);
    }
    /**
     * 执行nsq消息发布
     * @param string $cmd
     * @param array $nsqDatas
     */
    private function doPub($cmd, $nsqDatas) {
        $requestId = uniqid();
        try {
            if (empty($nsqDatas) || ! is_array($nsqDatas)) {
                throw new \InvalidArgumentException('NsqHttpClient: nsq data is empty or not an array');
            }
            $clientInfo = array(
                'userId' => self::$_userId,
                'corpId' => self::$_corpId,
                'host' => $this->_host,
                'port' => $this->_port,
                'topic' => $this->_topic,
                'cmd' => $cmd,
                'nsqDatas' => $nsqDatas
            );
            if ($cmd == 'mpub') {
                foreach ($nsqDatas as $item) {
                    $msgs[] = json_encode($item);
                }
                $message = implode("\n", $msgs);
            }
            else {
                $message = json_encode($nsqDatas);
            }
            $start = microtime(true);
            for ($i = 0; $i <= $this->_retryTimes; $i++) {
                try {
                    $response = $this->_nch->request(
                        'POST',
                        "/{$cmd}?topic={$this->_topic}",
                        ['body' => $message]
                    );
                    if ($result = ($response->getStatusCode() == 200)) {
                        $clientInfo['RequestTime'] = microtime(true) - $start;
                        break;
                    }
                    else {
                        throw new \Exception('post nsq message fail');
                    }
                } catch(\Exception $e) {
                    if ($i >= $this->_retryTimes) {
                        throw $e;
                    }
                }
            }
        } catch(\Exception $e) {

            throw $e;
        }
        return $result;
    }
    /**
     * 新建GuzzleHttp\Client实例
     */
    private function getHttpClient($config) {
        $this->config($config);	//加载配置
        //初始化一个GuzzleHttp\Client实例
        $this->_nch = new Client([
            'base_uri' => "http://{$this->_host}:{$this->_port}",
            'connect_timeout' => $this->_connectionTimeout,
            'timeout'  => $this->_readWriteTimeout
        ]);
    }
    /**
     * 获取Nsq服务模块配置
     * @throws \Exception
     */
    private function config($config) {

        if( ! isset($config['host']) || ! isset($config['http_port']) || ! isset($config['topic'])) {
            throw new \InvalidArgumentException('NsqHttpClient: Nsq host, http_port or topic is not set');
        }
        $this->_host = $config['host'];
        $this->_port = $config['http_port'];
        $this->_topic = $config['topic'];

        if(isset($config['connection_timeout'])) {
            $this->_connectionTimeout = intval($config['connection_timeout']);
        }
        if(isset($config['readwrite_timeout'])) {
            $this->_readWriteTimeout = intval($config['readwrite_timeout']);
        }
    }
    /**
     * 防止克隆
     */
    private function __clone() {
    }
}