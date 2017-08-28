<?php

/**
 * Created by PhpStorm. 异步任务
 * User: onsakuquan
 * Date: 17/8/26
 * Time: 下午4:26
 */
namespace phpkit\thriftrpc;
class Async
{
    protected $tcp;
    protected $className;
    protected $port;
    protected $host;
    protected $httpDedug;
    protected $xdebugSession;
    protected $constructParams = [];
    protected $callbacks = [];
    protected $errorCallbacks = [];

    /**
     * @param mixed $httpDedug
     */
    public function setHttpDedug($httpDedug)
    {
        $this->httpDedug = $httpDedug;
        return $this;
    }

    /**
     * @param mixed $host
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param mixed $xdebugSession
     */
    public function setXdebugSession($xdebugSession)
    {
        $this->xdebugSession = $xdebugSession;
        return $this;
    }

    /**
     * @param mixed $tcp
     */
    public function setTcp($tcp)
    {
        $this->tcp = $tcp;
        return $this;
    }

    /**
     * @param mixed $port
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param mixed $className
     */
    public function setClassName($className)
    {
        $this->className = $className;
        return $this;
    }

    public function __construct($className = "", $tcp = "", $port = "")
    {
        if ($tcp) {
            $this->tcp = $tcp;
        }
        if ($port) {
            $this->port = $port;
        }
        if ($className) {
            $this->className = $className;
        }
    }

    /**
     * @param array $constructParams
     */
    public function setConstructParams($constructParams)
    {
        $this->constructParams = $constructParams;
        return $this;
    }

//函数回调
    public function callBack($fun)
    {
        if (empty($fun)) {
            return false;
        }
        if (in_array($fun, $this->callbacks)) {
            return false;
        }
        $this->callbacks[] = $fun;
        return $this;
    }

//错误回调
    public function errorCallback($fun)
    {
        if (empty($fun)) {
            return false;
        }
        if (in_array($fun, $this->errorCallbacks)) {
            return false;
        }
        $this->errorCallBacks[] = $fun;
        return $this;
    }

    function __call($function_name, $args)
    {
        $data = array(
            'className' => $this->className,
            'method' => $function_name,
            'constructParams' => $this->constructParams,
            'args' => $args,

        );
        if ($this->httpDedug == 1) {
            return $this->http($data);
        } else {
            return $this->swoole(json_encode($data));
        }

    }

    function swoole($data)
    {
        //同步阻塞:SWOOLE_SOCK_SYNC , 异步:SWOOLE_SOCK_ASYNC
        if(PHP_SAPI =='cli'){
            //设置事件回调函数
            $client = new \Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
            $client->on("connect", function ($cli) use ($data) {
                $cli->send($data);
            });

            $client->on("receive", function ($cli, $data) {
                if (!empty($this->callbacks) && is_array($this->callbacks)) {
                    foreach ($this->callbacks as $func) {
                        $func($data);
                    }
                }
                $cli->close();
            });
            $client->on("error", function ($cli) {
                if (!empty($this->errorCallback) && is_array($this->errorCallback)) {
                    foreach ($this->errorCallback as $func) {
                        $func("Connect failed\n");
                    }
                }
            });
            $client->on("close", function ($cli) {
                echo "Connection close\n";
            });
            //发起网络连接
            $ret = $client->connect($this->tcp, $this->port, 0.5);
        }else{
            $fp = stream_socket_client("tcp://{$this->tcp}:{$this->port}", $code, $msg, 3);
            fwrite($fp, $data);
            if ((!empty($this->callbacks) && is_array($this->callbacks) )|| ( !empty($this->errorCallback) && is_array($this->errorCallback))) {
                throw  new \Exception("导步回调必须在PHP CLI 模式下运行。 async-io must be used in PHP CLI mode");
            }
        }

    }

    function http($params)
    {
        $params['XDEBUG_SESSION_START'] = $this->xdebugSession;
        //  $arg = http_build_query($params);

        $url = trim($this->host, "/")."/async/";
        $content = $this->mycurl($url, $params);
        $data = json_decode($content, 1);
        if ($data) {
            if (!empty($this->callbacks) && is_array($this->callbacks)) {
                foreach ($this->callbacks as $func) {
                    $func($data);
                }
            }
            return $data;
        } else {
            if (!empty($this->errorCallback) && is_array($this->errorCallback)) {
                foreach ($this->errorCallback as $func) {
                    $func($content);
                }
            }
            return $content;
        }
    }

    function mycurl($url, $post_file = array())
    {
        $ch = curl_init();
        //设置选项，包括URL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        if (is_array($post_file)) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_file)); ////传递一个作为HTTP "POST"操作的所有数据的字符
        }
        //执行并获取HTML文档内容
        $output = curl_exec($ch);
        //释放curl句柄
        curl_close($ch);
        return $output;
    }

}