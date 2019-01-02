<?php
namespace Muyu\Support;

class MuyuException extends \Exception
{
    private $detail;
    private $preException;
    function __construct($code = 0,$msg = '', $preException = null, $detail = null) {
        $this->code = $code;
        $this->message = $msg;
        $this->detail = $detail;
        $this->preException = $preException;
    }
    function ok() {
        return $this->code == 0;
    }
    function code() {
        return $this->code;
    }
    function msg() {
        return $this->message;
    }
    function detail() {
        return $this->detail;
    }
    function previous() {
        return $this->preException;
    }
    function trace() {
        $trace = [$this];
        $current = $this;
        while($current->preException != null) {
            $current = $current->preException;
            $trace[] = $current;
        }
        return $trace;
    }
    function dd() {
        $trace = $this->trace();
        $info = [];
        foreach ($trace as $e)
            $info[] = ['code' => $e->code(), 'msg'=> $e->msg(), 'detail' => $e->detail()];
        return $info;
    }
}