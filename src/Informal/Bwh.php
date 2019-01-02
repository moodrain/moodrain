<?php
namespace Muyu\Informal;
use Muyu\Support\Traits\MuyuExceptionTrait;
use Muyu\Curl;
use function Muyu\Support\Fun\conf;

// 非正式类，没有添加异常处理， 没有收集apiurl
class Bwh
{
    private $id;
    private $key;
    private $apiUrl;

    use MuyuExceptionTrait;
    function __construct($muyuConfig = 'bwh.default', $init = true) {
        $this->initError();
        if($init)
            $this->init(conf($muyuConfig));
    }
    function init($config) {
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->apiUrl = 'https://api.64clouds.com/v1';
    }
    function call($request, $param = []) {
        return $this->handle($request, $param);
    }
    function info() {
        return $this->handle('getServiceInfo');
    }
    function status() {
        return $this->handle('getLiveServiceInfo');
    }
    function ip() {
        $ips = $this->info()['ip_addresses'];
        return count($ips) == 1 ? $ips[0] : $ips;
    }
    function isRunning() {
        return $this->status()['ve_status'] == 'running' ? true : false;
    }
    function isMigrating() {
        $this->info();
        return $this->error->code == 788888;
    }
    function location() {
        $rs = $this->handle('migrate/getLocations');
        return  $rs ? $rs['currentLocation'] : false;
    }
    function migrate($location = null) {
        $rs = $this->handle('migrate/getLocations');
        if(!$rs)
            return false;
        $current = $rs['currentLocation'];
        $locations = $rs['locations'];
        if(!$location) {
            for($i = 0, $count = count($locations);$i < $count;$i++)
                if($locations[$i] == $current)
                    $location = $locations[($i + 1) % $count];
        }
        else if(!in_array($location, $locations)) {
            $this->addError(2, 'location not in list');
            return false;
        }
        else if($location == $current) {
            $this->addError(4, 'target location is the same as current');
            return false;
        }
        $rs = $this->handle('migrate/start', ['location' => $location]);
        if(!$rs)
            return false;
        $ips = $rs['newIps'];
        return count($ips) == 1 ? $ips[0] : $ips;
    }
    function stop() {
        return $this->handle('stop');
    }
    function start() {
        return $this->handle('start');
    }
    function restart() {
        return $this->handle('restart');
    }
    private function handle($request, $param = []) {
        $param = array_merge($param, ['veid' => $this->id, 'api_key' => $this->key]);
        $curl = new Curl();
        $rs = $curl->url($this->apiUrl . '/' . $request)->accept('json')->data($param)->post();
        if(!$rs) {
            $this->addError(1, 'request error', $curl->error());
            return false;
        }
        else if(isset($rs['error']) && $rs['error'] != 0) {
            $this->addError(5, 'api error', null, $rs['error']);
            return false;
        }
        return $rs;
    }
}