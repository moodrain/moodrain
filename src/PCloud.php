<?php
namespace Muyu;

use Muyu\Support\ApiUrl;
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
        foreach ($config as $key => $val)
            $this->$key = $val;
        $this->pass = base64_decode($this->pass);
        $this->api = ApiUrl::$urls['pCloud'];
        return $this;
    }

    public function get($file) {
        $link = $this->req('getFileLink', $file);
        var_dump($link);
    }

    public function put() {

    }

    public function del() {

    }

    public function list($path) {
        return $this->req('listFolder', ['path' => $path]);
    }

    public function req($action, $param) {
        $action = strtolower($action);
        $curl = new Curl($this->api);
        return $curl->path($action)->data(array_merge([
            'getauth' => 1,
            'logout' => 1,
            'username' => $this->user,
            'password' => $this->pass,
        ], $param))->post();
    }


}