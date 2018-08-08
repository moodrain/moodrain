<?php
namespace App\Support\Traits;

use Muyu\Support\MuyuException;

trait MuyuExceptionTrait
{
    private $error;

    function error()
    {
        if($this->error == null)
            $this->error = new MuyuException();
        return $this->error;
    }

    private function addError(MuyuException $error)
    {
        if(!$this->error)
            $this->error = new MuyuException();
        $this->error = $this->error->add($this->error, $error);
    }
}