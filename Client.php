<?php
namespace phpkit\thriftrpc;
// 引入客户端文件
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
	public function getProtocol($serviceName) {
		$socket = new TSocket("127.0.0.1", 8091);
		$transport = new TFramedTransport($socket);
		$protocol = new TBinaryProtocol($transport);
		$transport->open();
		return $protocol;
	}

	public function getRPCService($serviceName) {
		$protocol = $this->getProtocol($serviceName);
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
