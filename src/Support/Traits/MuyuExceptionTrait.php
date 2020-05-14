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


    private function addErr($err, ...$msgData) {
        if(is_string($err)) {
            $msg = $err;
            $code = 1;
        } else if(isset($err['msg'])) {
            $msg = $err['msg'];
            $code = $err['code'];
        } else {
            $code = $err['code'];
            $msg = $err['msg'];
        }
        $msgArr = explode('?', $msg);
        foreach($msgArr as $index => & $val) {
            $val .= $msgData[$index] ?? '';
        }
        $msg = implode('', $msgArr);
        $this->addError($code, $msg);
    }
}