<?php
namespace Muyu\Secondary;

use Muyu\Curl;
use Muyu\Support\Tool;
use Muyu\Support\Traits\MuyuExceptionTrait;
use Muyu\Support\Ali;
use Muyu\Support\ApiUrl;
use function Muyu\Support\Fun\conf;

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

    use MuyuExceptionTrait;
    public function __construct($muyuConfig = 'sms.default', $init = true) {
        $this->initError();
        if($init)
            $this->init(conf($muyuConfig));
    }
    public function init($config) {
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
    public function to($phone) {
        $this->phoneNumbers = is_array($phone) ? implode(',', $phone) : $phone;
        return $this;
    }
    public function data($data) {
        foreach($data as $key => $val)
            $data[$key] = strval($val);
        $this->templateParam = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }
    public function send() {
        $params = [];
        foreach($this as $key => $val)
            $params[ucfirst($key)] = $val;
        unset($params['AccessKeySecret']);
        unset($params['Error']);
        $url = ApiUrl::$urls['aliSNS'];
        $params = Ali::httpParam($params, $this->accessKeySecret);
        $curl = new Curl();
        $rs = $curl->url($url)->data($params)->accept('json')->post();
        if(!$curl) {
            $this->addError(1, 'request error', $curl->error());
            return false;
        }
        if($rs['Code'] == 'OK')
            return true;
        $this->addError(2, 'api error', null, $rs['Message']);
        return false;
    }
}