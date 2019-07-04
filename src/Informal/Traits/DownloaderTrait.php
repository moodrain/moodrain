<?php
namespace Muyu\Informal\Traits;
use Muyu\Curl;

trait DownloaderTrait
{
    protected $url;
    protected $folder;
    protected $size;
    protected $cookie;

    function setBasicField($url, $folder, $size, $cookie) {
        $this->url = $url;
        $this->folder = $folder;
        $this->size = $size;
        $this->cookie = $cookie;
    }
    function defaultRun($option = []) {
        $this->runCheck($option);
        $this->runHandle($option);
        $this->runFinish($option);
    }
    function run($option = []) {
        $this->defaultRun($option);
    }

    protected function goNext(Curl $curl, callable $handler = null) {
        if(!$handler)
            return !$curl->is404() && $curl->error()->ok();
        else
            return $handler($curl);
    }
    protected function save($file, Curl $curl) {
        if(strlen($curl->content()) > 1024 * 5)
            file_put_contents($this->folder . '/' . $file, $curl->content());
        unset($curl);
        $curl = new Curl();
    }
    protected function checkIntegrity() {
        if(file_exists(!$this->folder)) {
            echo PHP_EOL . 'check Integrity fail: ' . basename($this->folder) . ' dir not found';
            return;
        }
        $fileCount = count(scandir($this->folder)) - 2;
        if($fileCount < $this->size)
            echo PHP_EOL . 'check Integrity fail: ' . basename($this->folder) . ': ' . $fileCount . '/' . $this->size . ' ' . $this->url . PHP_EOL;
    }
    protected function check($field, $info = null) {
        if (is_array($field))
            foreach ($field as $f)
                if (!$this->$f)
                    $this->checkFail($info ?? $f . ' not set');
        if (is_string($field))
            if (!$this->$field)
                $this->checkFail($info ?? $field . ' not set');
    }
    protected function checkFail($info) {
        die($info);
    }
}