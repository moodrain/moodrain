<?php
namespace Muyu\Informal\Traits;
use Muyu\Curl;
use Muyu\Support\Tool;

trait DownloaderTrait
{
    private $url;
    private $folder;
    private $size;

    function setBasicField($url, $folder,$size) {
        $this->url = $url;
        $this->folder = $folder;
        $this->size = $size;
    }
    function defaultRun($option = []) {
        $this->runCheck($option);
        $this->runHandle($option);
        $this->runFinish($option);
    }

    private function goNext(Curl $curl, callable $handler = null) {
        if(!$handler)
            return !$curl->is404() && !$curl->error();
        else
            return $handler($curl);
    }
    private function save($file, Curl $curl) {
        file_put_contents($this->folder . '/' . $file, $curl->content());
    }
    private function checkIntegrity() {
        $fileCount = count(scandir($this->folder)) - 2;
        if($fileCount != $this->size)
            echo PHP_EOL . 'check Integrity fail: ' . basename($this->folder) . ': ' . $fileCount . '/' . $this->size . PHP_EOL;
    }
    private function check($field, $info = null) {
        if (is_array($field))
            foreach ($field as $f)
                if (!$this->$f)
                    $this->checkFail($info ?? $f . ' not set');
        if (is_string($field))
            if (!$this->$field)
                $this->checkFail($info ?? $field . ' not set');
    }
    private function checkFail($info) {
        die($info);
    }
}