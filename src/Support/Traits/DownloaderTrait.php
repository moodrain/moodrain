<?php
namespace App\Support\Traits;
use Muyu\Curl;

trait DownloaderTrait
{
    private $url;
    private $folder;
    private $size;

    public function setBasicField(string $url, $folder, int $size): void
    {
        $this->url = $url;
        $this->folder = $folder;
        $this->size = $size;
    }

    private function goNext(Curl $curl, callable $handler = null): bool
    {
        if (!$handler)
            return !$curl->is404() && !$curl->error();
        else
            return $handler($curl);
    }

    private function save(string $file, Curl $curl): void
    {
        file_put_contents($this->folder . '/' . $file, $curl->content());
    }

    private function check($field, string $info = null): void
    {
        if (is_array($field))
            foreach ($field as $f)
                if (!$this->$f)
                    $this->checkFail($info ?? $f . ' not set');
        if (is_string($field))
            if (!$this->$field)
                $this->checkFail($info ?? $field . ' not set');
    }

    private function checkFail(string $info): void
    {
        die($info);
    }
}