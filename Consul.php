<?php
namespace phpkit\thriftrpc;
Class Consul {
	protected $url = "http://consul.pdas.com:8500/v1";
	function myCurl($url, $data = null, $type = "put") {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url); //定义请求地址
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type); //定义请求类型，当然那个提交类型那一句就不需要了
		curl_setopt($ch, CURLOPT_HEADER, 0); //定义是否显示状态头 1：显示 ； 0：不显示
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //定义是否直接输出返回流
		if ($data) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //定义提交的数据
		}
		$res = curl_exec($ch);
		curl_close($ch); //关闭
		return $res;
	}
	//服务注册到consul
	function registerServices($data) {
		$url = $this->url . '/agent/service/register';
		$ret = $this->myCurl($url, $data);
		return $ret;
	}
	//查所有服务
	function listServices() {
		$url = $this->url . '/agent/services';
		$ret = $this->myCurl($url, null, "get");
		return $ret;
	}
	//删除服务
	function deregister($service_id) {
		$url = $this->url . '/agent/service/deregister/' . $service_id;
		$ret = $this->myCurl($url);
		return $ret;
	}

	//发现服务
	function findService($name) {
		$url = $this->url . '/catalog/service/' . $name;
		$ret = $this->myCurl($url);
		return json_decode($ret, true);
	}
//健康的服务
	function healthService(){
        $url = $this->url . '/health/state/passing';
        $ret = $this->myCurl($url);
        return json_decode($ret, true);
    }

}

// $url = 'http://consul.pdas.com:8500/v1/agent/service/register';
// $consul = new consul();
// $data = '{
// 				  "ID": "redis2",
// 				  "Name": "redis",
// 				  "Address": "127.0.0.1",
// 				  "Port": 8004,
// 				  "Check": {
// 				    "name": "status",
// 				    "tcp": "127.0.0.1:8092",
// 				    "Interval": "10s",
// 				    "timeout":"1s"
// 				  }
// 				}';
// $consul->registerServices($data);
// // $data = $consul->listServices();
// // var_dump(json_decode($data, true));
// $data = $consul->findService("redis");
// var_dump($data);
