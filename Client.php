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
	public function __construct($dirs = null) {
		if (is_array($dirs)) {
			$this->registerDefinition($dirs);
		}
	}
	public function getProtocol($serviceName, $serviceAddress = "", $servicePort = "") {
		//随机找到一个服务
		$consul = new \phpkit\consulapi\Consul();
		$services = $consul->findService($serviceName);
		$key = array_rand($services, 1);
		$serviceAddress = $serviceAddress ? $serviceAddress : $services[$key]['ServiceAddress'];
		$servicePort = $servicePort ? $servicePort : $services[$key]['ServicePort'];
		$socket = new TSocket($serviceAddress, $servicePort);
		$transport = new TFramedTransport($socket);
		$protocol = new TBinaryProtocol($transport);
		$transport->open();
		return $protocol;
	}

	public function getRPCService($serviceName, $serviceAddress = "", $servicePort = "") {
		$protocol = $this->getProtocol($serviceName, $serviceAddress, $servicePort);
		$arr = explode("\\", $serviceName);
		$name = $arr[count($arr) - 1];
		$tMultiplexedProtocol = new TMultiplexedProtocol($protocol, $serviceName);
		$service_class = $serviceName . "\\" . $name . "Client";
		$client = new $service_class($tMultiplexedProtocol);
		return $client;
	}

	public function registerDefinition($dirs) {
		$loader = new \Thrift\ClassLoader\ThriftClassLoader(true);
		foreach ($dirs as $key => $value) {
			$loader->registerDefinition($key, $value);
		}
		$loader->register();
	}

}
