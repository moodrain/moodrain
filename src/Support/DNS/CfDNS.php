<?php
namespace Muyu\Support\DNS;

use Muyu\Support\Traits\MuyuExceptionTrait;
use Muyu\Curl;
use Muyu\Support\ApiUrl;

class CfDNS
{
    private $email;
    private $key;
    private $api;

    use MuyuExceptionTrait;
    public function __construct($config) {
        $this->initError();
        $this->api = ApiUrl::$urls['cfDNS'];
        foreach ($config as $key => $val)
            $this->$key = $val;
    }
    function getRecords($domain, $options = []) {
        if(isset($options['name']))
            $options['name'] .= '.' . $domain;
        $options['per_page'] = 100;
        $curl = $this->getCurl();
        $zone = $this->getZone($domain);
        if(!$zone) {
            $this->addError(3, 'get zone error');
            return false;
        }
        $rs = $curl->path($zone . '/dns_records')->query($options)->get();
        if(!$rs)
            $this->addError(1, 'request error', $curl->error());
        else if(isset($rs['success']) && !$rs['success'])
            $this->addError(2, 'api error', null, $rs['messages']);
        if(!$this->error->ok())
            return false;
        return $this->error->ok() ? $this->transformRecord($rs['result']) : false;
    }
    function updateRecord($domain, $name, $value, $type = 'A') {
        $curl = $this->getCurl();
        $zone = $this->getZone($domain);
        if(!$zone) {
            $this->addError(3, 'get zone error');
            return false;
        }
        $rs = $curl->path($zone . '/dns_records/' . $this->getId($domain, $name))->json([
            'name' => $name,
            'content' => $value,
            'type' => $type,
        ])->put();
        if(!$rs)
            $this->addError(1, 'request error', $curl->error());
        else if(isset($rs['success']) && !$rs['success'])
            $this->addError(2, 'api error', null, $rs['messages']);
        return $this->error->ok() ? $rs['success'] : false;
    }
    private function transformRecord($records) {
        $return = [];
        foreach($records as $r)
            $return[] = new Record($r['id'], $r['type'], str_replace($r['zone_name'], '', $r['name']), $r['content'], $r['ttl']);
        return $return;
    }
    private function getId($domain, $name) {
        return $this->getRecords($domain, ['name' => $name])[0]->id ?? false;
    }
    private function getZone($domain) {
        $curl = $this->getCurl();
        $rs = $curl->query(['name' => $domain])->get();
        if(!$rs)
            $this->addError(1, 'request error', $curl->error());
        else if(isset($rs['success']) && !$rs['success'])
            $this->addError(2, 'api error', null, $rs['messages']);
        return $this->error->ok() ? $rs['result'][0]['id'] : false;
    }
    private function getCurl() {
        $curl = new Curl($this->api);
        $curl->header([
            'Content-Type' => 'application/json',
            'X-Auth-Key' => $this->key,
            'X-Auth-Email' => $this->email,
        ])->accept('json');
        if($this->ss ?? false)
            $curl->ss();
        return $curl;
    }
}