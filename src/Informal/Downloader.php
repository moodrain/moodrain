<?php
namespace Muyu\Informal;

use Muyu\Curl;
use Muyu\Informal\Traits\DownloaderTrait;
use Muyu\Support\Tool;

// 非正式类，没有添加异常处理， 没有收集apiurl
class Downloader
{
    use DownloaderTrait;
    function __construct($url = null, $folder = null, $size = null, $cookie = null) {
        $this->url = $url;
        $this->size = $size ?? 9999;
        $this->folder = $folder;
        $this->cookie = $cookie ?? [];
        set_time_limit(0);
        $this->folder = html_entity_decode(Tool::name($this->folder ?? 'E:/Download'));
        Tool::mkdir($this->folder);
    }
    function inOrder() {
        $curl = new Curl();
        for($i = 1;$i <= $this->size;$i++) {
            $curl->url(sprintf($this->url, $i))->get(false);
            if($this->goNext($curl))
                $this->save($i . '.' . Tool::ext($this->url), $curl);
            else
                break;
        }
        $curl->close();
    }
    function byStream($context, $filename = null) {
        $stream = fopen($this->url, 'r', false, stream_context_create($context));
        $filename = $filename ?? basename(explode('?', $this->url)[0]) ?? 'download';
        $file = fopen($this->folder . '/' . $filename, 'w');
        $buffer = null;
        while(true) {
            $buffer = fgets($stream, 1024000);
            fwrite($file, $buffer);
            if(feof($stream))
                break;
        }
        fclose($stream);
        fclose($file);
    }
    function url($url) {
        $this->url = $url;
        return $this;
    }
    function size($size) {
        $this->size = $size;
        return $this;
    }
    function folder($folder) {
        $this->folder = $folder;
        return $this;
    }
    function cookie($cookie) {
        $this->cookie = $cookie;
        return $this;
    }
}