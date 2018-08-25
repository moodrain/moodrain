<?php
namespace Muyu\Support;
use Muyu\Config;
use Muyu\Curl;

class Bwh
{
    private $id;
    private $key;
    private $apiUrl;
    private $error;

    public function __construct(string $muyuConfig = 'bwh.default', bool $init = true)
    {
        $config = new Config();
        if($init)
            $this->init($config($muyuConfig));
    }
    public function init(array $config) : void
    {
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->apiUrl = 'https://api.64clouds.com/v1';
    }
    public function call(string $request, array $param = [])
    {
        return $this->handle($request, $param);
    }
    public function info()
    {
        return $this->handle('getServiceInfo');
    }
    public function status()
    {
        return $this->handle('getLiveServiceInfo');
    }
    public function ip()
    {
        $ips = $this->info()['ip_addresses'];
        return count($ips) == 1 ? $ips[0] : $ips;
    }
    public function isRunning()
    {
        return $this->status()['ve_status'] == 'running' ? true : false;
    }
    public function isMigrating()
    {
        $this->info();
        return $this->error == 788888;
    }
    public function location()
    {
        $rs = $this->handle('migrate/getLocations');
        return  $rs ? $rs['currentLocation'] : false;
    }
    public function migrate(string $location = null)
    {
        $rs = $this->handle('migrate/getLocations');
        if(!$rs)
            return false;
        $current = $rs['currentLocation'];
        $locations = $rs['locations'];
        if(!$location)
        {
            for($i = 0, $count = count($locations);$i < $count;$i++)
                if($locations[$i] == $current)
                    $location = $locations[($i + 1) % $count];
        }
        else if(in_array($location, $locations))
        {
            $this->error = 'location not in list';
            return false;
        }
        else if($location == $current)
        {
            $this->error = 'location is the same as current';
            return false;
        }
        $rs = $this->handle('migrate/start', ['location' => $location]);
        if(!$rs)
            return false;
        $ips = $rs['newIps'];
        return count($ips) == 1 ? $ips[0] : $ips;
    }
    public function stop()
    {
        return $this->handle('stop');
    }
    public function start()
    {
        return $this->handle('start');
    }
    public function restart()
    {
        return $this->handle('restart');
    }
    private function handle(string $request, array $param = [])
    {
        $param = array_merge($param, ['veid' => $this->id, 'api_key' => $this->key]);
        $curl = new Curl();
        $rs = $curl->url($this->apiUrl . '/' . $request)->accept('json')->data($param)->post();
        if(!$rs)
        {
            $this->error = 'request fail:' . $curl->error();
            return false;
        }
        else if(isset($rs['error']) && $rs['error'] != 0)
        {
            $this->error = $rs['error'];
            return false;
        }
        $this->error = null;
        return $rs;
    }
    public function error()
    {
        return $this->error;
    }
}