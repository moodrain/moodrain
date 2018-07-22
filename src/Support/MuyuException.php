<?php
namespace Muyu\Support;

class MuyuException extends \Exception
{
    private $detail;
    private $preException;
    public function __construct(int $code, string $msg, $detail = null, $preException = null)
    {
        $this->code = $code;
        $this->message = $msg;
        $this->detail = $detail;
        $this->preException = $preException;
    }
}