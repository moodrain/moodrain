<?php
namespace Muyu\Support;

use Muyu\Config;
use Muyu\Curl;
use Muyu\Tool;

class DNS4ali
{
    private $commonParam;
    private $accessKeyId;
    private $accessKeySecret;
    private $error;
    private $apiUrl = 'https://alidns.aliyuncs.com';

    public function __construct($muyuConfig = 'ali.default', bool $init = true)
    {
        $config = new Config();
        if($init)
            $this->init($config('ali.default'));
    }
    public function init(array $config)
    {
        foreach ($config as $key => $val)
            $this->$key = $val;
        $this->initCommonParam();
    }
    public function getDomainRecords(string $domainName, string $rrKeyWord = null, string $typeKeyWord = null, string $valueKeyWord = null) : array
    {
        $serviceParam = [];
        $serviceParam['Action'] = 'DescribeDomainRecords';
        $serviceParam['DomainName'] = $domainName;
        $serviceParam['PageNumber'] = 1;
        $serviceParam['PageSize'] = 500;
        if($rrKeyWord)
            $serviceParam['RRKeyWord'] = $rrKeyWord;
        if($typeKeyWord)
            $serviceParam['TypeKeyWord'] = $typeKeyWord;
        if($valueKeyWord)
            $serviceParam['ValueKeyWord'] = $valueKeyWord;
        $sendParam = Ali::httpParam(array_merge($this->commonParam, $serviceParam), $this->accessKeySecret);
        $curl = new Curl();
        $rs = $curl->url($this->apiUrl)->data($sendParam)->receive('json')->post();
        $this->error = $rs['Message'] ?? null;
        return $rs['DomainRecords']['Record'] ?? [];
    }
    public function updateDomainRecord(String $recordId, String $rr, string $value, string $type = 'A', int $ttl = 600, int $priority = null, String $line = 'default') : bool
    {
        $serviceParam = [];
        $serviceParam['Action'] = 'UpdateDomainRecord';
        $serviceParam['RecordId'] = $recordId;
        $serviceParam['RR'] = $rr;
        $serviceParam['Type'] = $type;
        $serviceParam['Value'] = $value;
        $serviceParam['TTL'] = $ttl;
        if($priority)
            $serviceParam['Priority'] = $priority;
        $serviceParam['Line'] = $line;
        $sendParam = Ali::httpParam(array_merge($this->commonParam, $serviceParam), $this->accessKeySecret);
        $curl = new Curl();
        $rs = $curl->url($this->apiUrl)->data($sendParam)->receive('json')->post();
        $this->error = $rs['Message'] ?? null;
        return !isset($rs['Message']);
    }
    public function updateDomainAByRR(string $domain, string $rr, string $ip)
    {
        $domains = $this->getDomainRecords($domain);
        if(!$domains)
            return false;
        $recordId = null;
        foreach($domains as $domain)
            if($domain['RR'] == $rr)
                $recordId = $domain['RecordId'];
        if(!$recordId)
        {
            $this->error = 'RR not found';
            return false;
        }
        return $this->updateDomainRecord($recordId, $rr, $ip);
    }
    private function initCommonParam()
    {
        $format = 'JSON';
        $version = '2015-01-09';
        $accessKeyId = $this->accessKeyId;
        $signatureMethod = 'HMAC-SHA1';
        $timestamp = Tool::gmt_iso8601();
        $signatureVersion = '1.0';
        $signatureNonce = Tool::uuid();
        $this->commonParam = [
            'Format' => $format,
            'Version' => $version,
            'AccessKeyId' => $accessKeyId,
            'SignatureMethod' => $signatureMethod,
            'Timestamp' => $timestamp,
            'SignatureVersion' => $signatureVersion,
            'SignatureNonce' => $signatureNonce,
        ];
    }
    public function domainRecordConst()
    {
        return new Class {
            public $type_A = 'A';
            public $type_NS = 'NS';
            public $type_MX = 'MX';
            public $type_TXT = 'TXT';
            public $type_CNAME = 'CNAME';
            public $type_SRV = 'SRV';
            public $type_AAAA = 'AAAA';
            public $type_CAA = 'CAA';
            public $type_REDIRECT_URL = 'REDIRECT_URL';
            public $type_FORWARD_URL = 'FORWARD_URL';
            public $priority_min = 0;
            public $priority_max = 10;
        };
    }
    public function error()
    {
        return $this->error;
    }
}