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
    protected $constructParams = [];

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



    function __call($function_name, $args)
    {
        $fp = stream_socket_client("tcp://" . $this->tcp . ":" . $this->port, $errno, $errstr, 30);
        if (!$fp) {
            throw  new \Exception("$errstr ($errno)<br />\n");
        }
        $data = array(
            'className' => $this->className,
            'method' => $function_name,
            'constructParams'=>$this->constructParams,
            'args' => $args,

        );

        fwrite($fp, json_encode($data));
        fclose($fp);

    }

}