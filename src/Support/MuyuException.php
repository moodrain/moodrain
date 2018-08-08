<?php
namespace Muyu\Support;

class MuyuException extends \Exception
{
    private $detail;
    private $preException;
    function __construct(int $code = 0, string $msg = '', $detail = null, $preException = null)
    {
        $this->code = $code;
        $this->message = $msg;
        $this->detail = $detail;
        $this->preException = $preException;
    }

    function ok() : bool
    {
        return $this->code == 0;
    }

    function code() : int
    {
        return $this->code;
    }

    function msg() : string
    {
        return $this->message;
    }

    function detail()
    {
        return $this->detail;
    }

    function previous() : MuyuException
    {
        return $this->preException;
    }

    function add(MuyuException $pre, MuyuException $new) : MuyuException
    {
        if($this->ok())
            return $new;
        $new->preException = $pre;
        return $new;
    }

    function trace() : array
    {
        $trace = [$this];
        $current = $this;
        while($current->preException != null)
        {
            $current = $current->preException;
            $trace[] = $current;
        }
        return $trace;
    }

    function dd() : array
    {
        $trace = $this->trace();
        $info = [];
        foreach ($trace as $e)
            $info[] = ['code' => $e->code(), 'msg'=> $e->msg()];
        return $info;
    }
}