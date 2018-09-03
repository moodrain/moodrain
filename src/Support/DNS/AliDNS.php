<?php
namespace Muyu\Support\DNS;

use Muyu\Curl;
use Muyu\Support\Ali;
use Muyu\Tool;
class AliDNS
{
    private $commonParam;
    private $accessKeyId;
    private $accessKeySecret;
    private $error;
    private $apiUrl = 'https://alidns.aliyuncs.com';

    public function __construct(array $config)
    {
        foreach ($config as $key => $val)
            $this->$key = $val;
    }
    public function getRecords(string $domainName, array $options = []) : array
    {
        $this->init();
        $serviceParam = [];
        $serviceParam['Action'] = 'DescribeDomainRecords';
        $serviceParam['DomainName'] = $domainName;
        $serviceParam['PageNumber'] = 1;
        $serviceParam['PageSize'] = 500;
        if(isset($options['name']))
            $serviceParam['RRKeyWord'] = $options['name'];
        if(isset($options['type']))
            $serviceParam['TypeKeyWord'] = $options['type'];
        if(isset($options['value']))
            $serviceParam['ValueKeyWord'] = $options['value'];
        $sendParam = Ali::httpParam(array_merge($this->commonParam, $serviceParam), $this->accessKeySecret);
        $curl = new Curl();
        $rs = $curl->url($this->apiUrl)->data($sendParam)->accept('json')->post();
        $this->error = $rs['Message'] ?? null;
        return $rs['DomainRecords']['Record'] ?? [];
    }
    public function updateDomainRecord(String $recordId, String $rr, string $value, string $type = 'A', int $ttl = 600, int $priority = null, String $line = 'default') : bool
    {
        $this->init();
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
        $rs = $curl->url($this->apiUrl)->data($sendParam)->accept('json')->post();
        $this->error = $rs['Message'] ?? null;
        return !isset($rs['Message']);
    }
    public function updateRecord(string $domain, string $rr, string $value, $type = 'A')
    {
        $domains = $this->getRecords($domain);
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
        return $this->updateDomainRecord($recordId, $rr, $value, $type);
    }
    private function init()
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
    public function error()
    {
        return $this->error;
    }
}