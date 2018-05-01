<?php
namespace Muyu;

use Muyu\Support\Ali;

class SMS
{
    private $accessKeyId;
    private $accessKeySecret;
    private $signatureMethod;
    private $signatureVersion;
    private $signName;
    private $templateCode;
    private $templateParam;
    private $phoneNumbers;
    private $format;
    private $action;
    private $regionId;
    private $timestamp;
    private $signatureNonce;
    private $outId;
    private $version;
    private $error;

    public function __construct(string $muyuConfig = 'sms.default', $init = true)
    {
        $config = new Config();
        if($init)
            $this->init($config($muyuConfig));
    }
    public function init(array $config) : SMS
    {
        foreach ($config as $key => $val)
            $this->$key = $val;
        $this->signatureMethod = 'HMAC-SHA1';
        $this->signatureVersion = '1.0';
        $this->format = 'JSON';
        $this->action = 'SendSMS';
        $this->regionId = 'cn-hangzhou';
        $this->timestamp = Tool::gmt_iso8601();
        $this->signatureNonce = Tool::uuid();
        $this->outId = uniqid();
        $this->version = '2017-05-25';
        return $this;
    }
    public function to($phone) : SMS
    {
        $this->phoneNumbers = is_array($phone) ? implode(',', $phone) : $phone;
        return $this;
    }
    public function data(array $data) : SMS
    {
        foreach($data as $key => $val)
            $data[$key] = strval($val);
        $this->templateParam = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }
    public function send() : bool
    {
        $params = [];
        foreach($this as $key => $val)
            $params[ucfirst($key)] = $val;
        unset($params['AccessKeySecret']);
        unset($params['Error']);
        $url = 'https://dysmsapi.aliyuncs.com';
        $params = Ali::httpParam($params, $this->accessKeySecret);
        $curl = new Curl();
        $rs = $curl->url($url)->data($params)->receive('json')->post();
        $curl->close();
        if($rs['Code'] == 'OK')
            return true;
        else
        {
            $this->error = $rs['Message'];
            return false;
        }
    }
    public function error() : string
    {
        return $this->error;
    }
}