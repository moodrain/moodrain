<?php
namespace Muyu\Support;

use Muyu\Config;
use Muyu\Support\DNS\AliDNS;
use Muyu\Support\DNS\CfDNS;

class DNS
{
    private $provider;
    private $domain;

    function __construct(string $muyuConfig = 'dns.default', bool $init = true) {
        if($init) {
            $config = new Config();
            $this->init($config($muyuConfig));
        }
    }
    function init(array $config){
        foreach ($config as $key => $val)
            $this->$key = $val;
        switch($this->provider) {
            case self::ProviderType()->aliyun: $this->provider = new AliDNS($config);break;
            case self::ProviderType()->cloudfare: $this->provider = new CfDNS($config);break;
            default: $this->provider = false;
        }
        return $this;
    }
    function error() {
        return $this->provider->error();
    }
    function domain(string $domain = null) {
        if($domain) {
            $this->domain = $domain;
            return $this;
        }
        return $this->domain;
    }
    function getRecords(array $options = []) {
        return $this->provider->getRecords($this->domain, $options);
    }
    function getRecord(string $name) {
        return $this->provider->getRecords($this->domain, ['name' => $name])[0] ?? null;
    }
    function updateRecord(string $name, string $value, string $type = 'A') {
        return $this->provider->updateRecord($this->domain, $name, $value, $type);
    }

    static function ProviderType() {
        return new class {
            public $aliyun = 'aliyun';
            public $cloudfare = 'cloudfare';
        };
    }
    static function RecordType()
    {
        return new class {
            public $A = 'A';
            public $AAAA = 'AAAA';
            public $NS = 'NS';
            public $MX = 'MX';
            public $TXT = 'TXT';
            public $CNAME = 'CNAME';
            public $SRV = 'SRV';
            public $LOC = 'LOC';
            public $CAA = 'CAA';
            public $SPF = 'SPF';
            public $CERT = 'CERT';
            public $DNSKEY = 'DNSKEY';
            public $DS = 'DS';
            public $SMIMEA = 'SMIMEA';
            public $SSHFP = 'SSHFP';
            public $TLSA = 'TLSA';
            public $URI = 'URL';
            public $ali_REDIRECT_URL = 'REDIRECT_URL';
            public $ali_FORWARD_URL = 'FORWARD_URL';
            public $ali_priority_min = 0;
            public $ali_priority_max = 10;
        };
    }
}
