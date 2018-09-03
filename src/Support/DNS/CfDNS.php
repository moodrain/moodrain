<?php
namespace Muyu\Support\DNS;

use Muyu\Curl;
class CfDNS
{
    private $email;
    private $key;
    private $api = 'https://api.cloudflare.com/client/v4/zones/';

    public function __construct(array $config) {
        foreach ($config as $key => $val)
            $this->$key = $val;
    }
    function getRecords(string $domain, array $options = []) {
        if(isset($options['name']))
            $options['name'] .= '.' . $domain;
        return $this->getCurl()->path($this->getZone($domain) . '/dns_records')->query($options)->get()['result'];
    }
    function updateRecord(string $domain, string $name, string $value, string $type = 'A') {
        return $this->getCurl()->path($this->getZone($domain) . '/dns_records/' . $this->getId($domain, $name))->json([
            'name' => $name,
            'content' => $value,
            'type' => $type,
        ])->put()['success'] ?? false;
    }
    private function getId(string $domain, string $name) {
        return $this->getRecords($domain, ['name' => $name])[0]['id'] ?? false;
    }
    private function getZone(string $domain) {
        $zones = $this->getCurl()->query(['name' => $domain])->get();
        return $zones['result'][0]['id'] ?? false;
    }
    private function getCurl() {
        $curl = new Curl('https://api.cloudflare.com/client/v4/zones/');
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