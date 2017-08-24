<?php
namespace phpkit\thriftrpc;
// 引入客户端文件
require_once __DIR__ . "/apc.php";
require_once __DIR__ . "/Thrift/ClassLoader/ThriftClassLoader.php";
use \Thrift\ClassLoader\ThriftClassLoader;
use \Thrift\Protocol\TBinaryProtocol;
use \Thrift\Protocol\TMultiplexedProtocol;
use \Thrift\Transport\TFramedTransport;
use \Thrift\Transport\TSocket;

class Client {
    protected $time = 0;
    protected $xdebugSession;
    protected $redis;
    protected $httpDedug;

    public function __construct($dirs = null) {
        if (is_array($dirs)) {
            $this->registerDefinition($dirs);
        }
    }
    function setXdebugSession($xdebugSession){
        $this->xdebugSession = $xdebugSession;
    }

    /**
     * @param mixed $http
     */
    public function setHttpDedug($http)
    {
        $this->httpDedug = $http;
    }

    /**
     * @param mixed $redis
     */
    public function setRedis($redis)
    {
        $this->redis = $redis;
    }

    public function getProtocol($serviceName, $serviceAddress = "", $servicePort = "") {
        if(empty($this->redis)){
            throw new \Exception( "服务发现是基于redis,请先设置redis");
        }
        $this->time++; //尝试次数
        try {
            if (!($serviceAddress && $servicePort)) {
                $services=$this->redis->sMembers($serviceName);
                $key = array_rand($services, 1); //随机找到一个服务
                $service = explode("@",$services[$key]);
                $serviceAddress = $service[0];
                $servicePort = $service[1];
            }
            $socket = new TSocket($serviceAddress, $servicePort);
            $transport = new TFramedTransport($socket);
            $protocol = new TBinaryProtocol($transport);
            $transport->open();
        } catch (\Exception $e) {
            if ($this->time > 5) {
                $this->getProtocol($serviceName, $serviceAddress, $servicePort);
            } else {
                throw new \Exception($serviceAddress . ":" . $servicePort . " 连接不上");

            }
        }
        $this->time = 0;
        return $protocol;
    }
//使用http来测试
    public function getHttpService($serviceName){
        if(empty($this->redis)){
            throw new \Exception( "服务发现是基于redis,请先设置redis");
        }
        $services=$this->redis->sMembers($serviceName);
        $key = array_rand($services, 1); //随机找到一个服务
        $service = explode("@",$services[$key]);
        return  $service[2];
    }

    public function getRPCService($serviceName, $serviceAddress = "", $servicePort = "") {
        //如果使用http来调用请求服务
        if($this->httpDedug==1 || strpos($serviceAddress,"http")===0){
            if(empty($serviceAddress)){
                $serviceAddress = $this->getHttpService($serviceName);
            }
            $client = new Http($serviceAddress,$servicePort);
            $client->setServiceName($serviceName);
            if($this->xdebugSession){
                $client->setXdebugSession($this->xdebugSession);
            }
            return $client;
        }else{
            $protocol = $this->getProtocol($serviceName, $serviceAddress, $servicePort);
            $arr = explode("\\", $serviceName);
            $name = $arr[count($arr) - 1];
            $tMultiplexedProtocol = new TMultiplexedProtocol($protocol, $serviceName);
            $service_class = $serviceName . "\\" . $name . "Client";
            $client = new $service_class($tMultiplexedProtocol);
            return $client;
        }


    }

    public function registerDefinition($dirs) {
        $loader = new \Thrift\ClassLoader\ThriftClassLoader(true);
        foreach ($dirs as $key => $value) {
            if (is_dir($value)) {
                $loader->registerDefinition($key, $value);
            } else {
                throw new \Exception($value . " is not dir");

            }
        }
        $loader->register();
    }

}
