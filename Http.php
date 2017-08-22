<?php
namespace phpkit\thriftrpc;
// 引入客户端文件
require_once __DIR__ . "/apc.php";


class Http {
	protected $host ;

    protected $port="80" ;

    protected $serviceName="" ;



    protected $XDEBUG_SESSION_START="12087";

	public function __construct($host,$port) {
		 $this->host = $host;
         $this->prot = $port;
	}

	function setHost($host){
	    $this->host = $host;
    }

    function setPort($port){
        $this->port = $port;
    }

    function setServiceName($serviceName){
        $this->serviceName = $serviceName;
    }

    public function theme_method($class, $method, $args) {
        $fire_args = array();

        $reflection = new \ReflectionMethod($class, $method);

        foreach ($reflection->getParameters() AS $k=>$arg) {
            $fire_args[$arg->name] = $args[$k];

        }
        return $fire_args;
    }
    function __call($function_name, $args){
        $arr = explode("\\", $this->serviceName);
        $name = $arr[count($arr) - 1];
        $service_class = $this->serviceName . "\\" . $name . "Client";
        if(!class_exists($service_class)){
            throw new \Exception("class_no_exists: $service_class");
        }
        $params =  $this->theme_method($service_class,$function_name,$args);
        $params['XDEBUG_SESSION_START'] = $this->XDEBUG_SESSION_START;
      //  $arg = http_build_query($params);
        $url =trim($this->host,"/")."/".$name."/".$function_name."/";
        $content = $this->mycurl($url,$params);
        $data =json_decode($content,1);
        if($data){
            return $data;
        }else{
          return $content;
        }
        // echo "你所调用的函数：$function_name(参数：<br />";
       // var_dump($args);
        //echo ")不存在！";
    }


    function mycurl($url,$post_file=array()){
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if(is_array($post_file)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_file); ////传递一个作为HTTP "POST"操作的所有数据的字符
        }
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

}
