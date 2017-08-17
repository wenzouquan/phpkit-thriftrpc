<?php
namespace phpkit\thriftrpc;
// 引入客户端文件
require_once __DIR__ . "/Thrift/ClassLoader/ThriftClassLoader.php";
use Thrift\ClassLoader\ThriftClassLoader;
use Thrift\Protocol\TBinaryProtocol;
use Thrift\Protocol\TMultiplexedProtocol;
use Thrift\Transport\TFramedTransport;
use Thrift\Transport\TSocket;

$GEN_DIR = __DIR__ . '/Structs/gen-php';
$loader = new ThriftClassLoader();
$loader->registerNamespace('Thrift', __DIR__);
$loader->registerNamespace('Swoole', __DIR__);
$loader->registerNamespace('Services', $GEN_DIR);
$loader->registerDefinition('Services', $GEN_DIR);
$loader->register();

class Client {

	public function getProtocol() {
		$socket = new TSocket("127.0.0.1", 8091);
		$transport = new TFramedTransport($socket);
		$protocol = new TBinaryProtocol($transport);
	}
	public function getRPCService($serviceName, $protocol) {
		$tMultiplexedProtocol = new TMultiplexedProtocol($protocol, $serviceName);
		$service_class = "\\Services\\" . $serviceName . "\\" . $serviceName . "Client";
		$client = new $service_class($tMultiplexedProtocol);
		return $client;
	}

	public function registerDefinition($dirs) {
		$loader = new \Thrift\ClassLoader\ThriftClassLoader();
		foreach ($dirs as $key => $value) {
			$loader->registerDefinition($key, $value);
		}
		$loader->register();
	}

}

// $socket = new TSocket("127.0.0.1", 8091);
// $transport = new TFramedTransport($socket);
// $protocol = new TBinaryProtocol($transport);
// //preg_match('/-\?(.+):/i','-?Hi2Service:myName',$match);
// //print_r($match);
// $transport->open();
// $stime = microtime(true);
// $count = 1;
// try {

// 	$rets = testHi2Service($protocol);
// 	foreach ($rets as $num) {
// 		echo $num, "\n";
// 	}

// 	$rets2 = testHiService($protocol);
// 	foreach ($rets2 as $num) {
// 		echo $num, "\n";
// 	}

// 	$etime = microtime(true);
// 	$total = $etime - $stime;
// 	echo "<br />[执行{$count}次,通过Thrift执行页面执行时间：{$total} ]秒";
// } catch (Exception $e) {
// 	var_dump($e->getMessage());
// }

// function testHi2Service($protocol) {
// 	for ($i = 0; $i < 1000; $i++) {
// 		$serviceName = "Hi2Service";
// 		$client = getRPCService($serviceName, $protocol);
// 		$ret = yield $client->myName("wen", 12);
// 	}
// }

// function testHiService($protocol) {
// 	for ($i = 0; $i < 1000; $i++) {
// 		$serviceName = "HiService";
// 		$client = getRPCService($serviceName, $protocol);
// 		$ret = yield $client->say("wen");
// 	}
// }

// $transport->close();
// function getRPCService($serviceName, $protocol) {
// 	$tMultiplexedProtocol = new TMultiplexedProtocol($protocol, $serviceName);
// 	$service_class = "\\Services\\" . $serviceName . "\\" . $serviceName . "Client";
// 	$client = new $service_class($tMultiplexedProtocol);
// 	return $client;
// }
