<?php
namespace Muyu\Support\DNS;

use Muyu\Support\Traits\MuyuExceptionTrait;
use Muyu\Curl;
use Muyu\Support\Ali;
use Muyu\Support\ApiUrl;
use Muyu\Support\Tool;
class AliDNS
{
    private $commonParam;
    private $accessKeyId;
    private $accessKeySecret;
    private $apiUrl;

    use MuyuExceptionTrait;
    function __construct($config) {
        $this->initError();
        $this->apiUrl = ApiUrl::$urls['aliDNS'];
        foreach ($config as $key => $val)
            $this->$key = $val;
    }
    function getRecords($domainName, $options = []) {
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
        if(!$rs)
            $this->addError(1, 'request error', $curl->error());
        else if(isset($rs['Message']) && $rs['Message'])
            $this->addError(2, 'api error', null, $rs['Message']);
        return $this->error->ok() ? $this->transformRecord($rs['DomainRecords']['Record']) : false;
    }
    function updateDomainRecord($recordId, $rr, $value, $type = 'A', $ttl = 600, $priority = null, $line = 'default') {
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
        if(!$rs)
            $this->addError(1, 'request error', $curl->error());
        else if(isset($rs['Message']) && $rs['Message'])
            $this->addError(2, 'api error', null, $rs['Message']);
        return $this->error->ok() ? !isset($rs['Message']) : false;
    }
    function updateRecord($domain, $rr, $value, $type = 'A') {
        $domains = $this->getRecords($domain) ?? [];
        $recordId = null;
        foreach($domains as $domain)
            if($domain->rr == $rr)
                $recordId = $domain->id;
        if(!$recordId) {
            $this->addError(3, 'record not found');
            return false;
        }
        return $this->updateDomainRecord($recordId, $rr, $value, $type);
    }
    private function transformRecord($records) {
        $return = [];
        foreach($records as $r)
            $return[] = new Record($r['RecordId'], $r['Type'], $r['RR'], $r['Value'], $r['TTL']);
        return $return;
    }
    private function init() {
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
}