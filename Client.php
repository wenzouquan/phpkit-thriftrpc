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
    public function __construct($dirs = null) {
        if (is_array($dirs)) {
            $this->registerDefinition($dirs);
        }
    }
    function setXdebugSession($xdebugSession){
        $this->xdebugSession = $xdebugSession;
    }
    public function getProtocol($serviceName, $serviceAddress = "", $servicePort = "") {
        $this->time++; //尝试次数
        try {
            if (!($serviceAddress && $servicePort)) {
                $consul = new \phpkit\consulapi\Consul();
                $services = $consul->findService($serviceName);
                $key = array_rand($services, 1); //随机找到一个服务
                $serviceAddress = $services[$key]['ServiceAddress'];
                $servicePort = $services[$key]['ServicePort'];
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

    public function getRPCService($serviceName, $serviceAddress = "", $servicePort = "") {
        //如果使用http来调用请求服务
        if(strpos($serviceAddress,"http")===0){
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
