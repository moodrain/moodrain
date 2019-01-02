<?php
namespace Muyu\Support\Traits;

use Muyu\Support\MuyuException;

trait MuyuExceptionTrait
{
    private $error;

    function error() {
        return $this->error;
    }
    private function initError(){
        $this->error = new MuyuException();
    }
    private function addError($code, $msg = '', MuyuException $preError = null, $detail = null) {
        $newError = null;
        if($preError)
            $newError = new MuyuException($code, $msg, $preError, $detail);
        else if($this->error->ok())
            $newError = new MuyuException($code, $msg, null, $detail);
        else
            $newError = new MuyuException($code, $msg, $this->error, $detail);
        $this->error = $newError;
        return $this->error;
    }
}