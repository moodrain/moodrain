<?php
namespace Muyu\Support;

use Muyu\Curl;
use function Muyu\Support\Fun\conf;
use Muyu\Support\Traits\MuyuExceptionTrait;

class PCloud {

    private $user;
    private $pass;
    private $api;

    use MuyuExceptionTrait;

    public function __construct($muyuConfig = 'pcloud.default', $init = true) {
        $this->initError();
        if($init) {
            $this->init(conf($muyuConfig));
        }
    }

    public function init($config) {
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }
        $this->pass = base64_decode($this->pass);
        $this->api = ApiUrl::$urls['pCloud'];
        return $this;
    }

    public function get($file) {
        return file_get_contents('http://' . $this->link($file));
    }

    public function link($file) {
        $file = $this->req('getFileLink', ['path' => $file]);
        return $file['hosts'][0] . $file['path'];
    }

    public function put($local, $remote) {
        $name = basename($remote);
        return $this->req('uploadFile', [
            'path' => str_replace('\\', '/', dirname($remote)),
        ], [
            'file' => $local,
            'name' => $name,
        ])['result'] == 0;
    }

    public function del($file) {
        return $this->req('deleteFile', ['path' => $file])['result'] == 0;
    }

    public function list($path, $withFolder = false) {
        if(! $withFolder) {
            $list = $this->req('listFolder', ['path' => $path])['metadata']['contents'];
            foreach($list as $index => $item) {
                if($item['isfolder']) {
                    unset($list[$index]);
                } else {
                    $list[$index] = $item['path'];
                }
            }
            return array_values($list);
        } else {
            return $this->req('listFolder', [
                'path' => $path,
                'recursive' => true,
            ])['metadata']['contents'];
        }
    }

    public function req($action, $param = [], $file = null) {
        $action = strtolower($action);
        $curl = new Curl($this->api);
        if(isset($param['path']) && $param['path']) {
            $param['path']{0} != '/' && ($param['path'] = '/' . $param['path']);
        }
        $curl->path($action)->data(array_merge([
            'getauth' => 1,
            'logout' => 1,
            'username' => $this->user,
            'password' => $this->pass,
        ], $param))->accept('json');
        $file && $curl->file('file', $file['file'], $file['name']);
        return $curl->post();
    }


}