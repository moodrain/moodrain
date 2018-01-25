<?php
namespace Muyu;

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
    public function data(array $data, bool $setReceiver = true) : SMS
    {
//        if(Tool::deep($data) == 2)
//        {
//            $phones = [];
//            foreach($data as $key => $val)
//            {
//                $phones[] = $key;
//                foreach($val as $keyy => $vall)
//                    $data[$key][$keyy] = strval($vall);
//            }
//            if($setReceiver)
//                $this->to($phones);
//        }
//        else
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
        $toSign = $this->ali_httpString('GET', $params);
        $Signature = $this->sign($this->accessKeySecret, $toSign);
        $url = 'https://dysmsapi.aliyuncs.com/?Signature=' . $Signature . '&' . http_build_query($params);
        $curl = new Curl();
        $rs = $curl->url($url)->receive('json')->get();
        if($rs['Code'] == 'OK')
            return true;
        else
        {
            $this->error = $rs['Message'];
            return false;
        }
    }
    private function sign(string $secret, string $str) : string
    {
        return Tool::ali_urlEncode(base64_encode(hash_hmac('sha1', $str, $secret . '&', true)));
    }
    private function ali_urlEncode(string $str) : string
    {
        $str = urlencode($str);
        $str = str_replace('+' , '%20', $str);
        $str = str_replace('*', '%2A', $str);
        $str = str_replace('%7A', '~', $str);
        return $str;
    }
    private function ali_httpString(string $method, array $params) : string
    {
        ksort($params, SORT_STRING);
        $str = '';
        foreach($params as $key => $val)
            $str = $str . '&' . $this->ali_urlEncode($key) . '=' . $this->ali_urlEncode($val);
        $str = substr($str, 1);
        $str = strtoupper($method) . '&' . $this->ali_urlEncode('/') . '&' .Tool::ali_urlEncode($str);
        return $str;
    }
    public function error() : string
    {
        return $this->error;
    }
}