<?php
namespace Muyu;

class SMS
{
    private $accessKeyId;
    private $accessKeySecret;
    private $endPoint;
    private $topic;
    private $freeSignName;
    private $templateCode;
    private $type;
    private $directSMS;
    private $error;
    private $template =
        '<?xml version="1.0" encoding="utf-8"?>
            <Message xmlns="http://mns.aliyuncs.com/doc/v1/">
            <MessageBody>content</MessageBody>
            <MessageAttributes>
                <DirectSMS>%s</DirectSMS>
            </MessageAttributes>
        </Message>';

    public function __construct(string $muyuConfig = 'sms', $init = true)
    {
        $config = new Config();
        if($init)
            $this->init($config($muyuConfig));
    }
    public function init(array $config) : SMS
    {
        foreach ($config as $key => $val)
            $this->$key = $val;
        $this->directSMS['FreeSignName'] = $this->freeSignName;
        $this->directSMS['TemplateCode'] = $this->templateCode;
        $this->directSMS['Type'] = $this->type;
        return $this;
    }
    public function to($phone) : SMS
    {
        $this->directSMS['Receiver'] = is_array($phone) ? implode(',', $phone) : $phone;
        return $this;
    }
    public function data(array $data, bool $setReceiver = true)
    {
        if(Tool::deep($data) == 2)
        {
            if($this->type != 'multiContent')
            {
                $this->error = 'this SMS type is not multiContent';
                return false;
            }
            $phones = [];
            foreach($data as $key => $val)
            {
                $phones[] = $key;
                foreach($val as $keyy => $vall)
                    $data[$key][$keyy] = strval($vall);
            }
            if($setReceiver)
                $this->to($phones);
        }
        else
            foreach($data as $key => $val)
                $data[$key] = strval($val);
        $this->directSMS['SmsParams'] = $data;
        return $this;
    }
    public function send()
    {
        $resource = 'topics/' . $this->topic . '/messages';
        $this->directSMS['SmsParams'] = json_encode($this->directSMS['SmsParams'], JSON_UNESCAPED_UNICODE);
        $data = sprintf($this->template, json_encode($this->directSMS, JSON_UNESCAPED_UNICODE));
        return (new Curl())->url('https://' . $this->endPoint . '/' . $resource)->header([
            'Authorization' => $this->sign('post', $resource),
            'Content-Length' => strlen($data),
            'Content-Type' => 'text/xml',
            'Date' => Tool::gmt(),
            'Host' => $this->endPoint,
            'x-mns-version' => '2015-06-06',
        ])->receive('xml')->string($data)->post();
    }
    private function sign(string $method, string $resource) : string
    {
        $method = strtoupper($method);
        $resource = '/' . $resource;
        $accessKeyId = $this->accessKeyId;
        $accessKeySecret = $this->accessKeySecret;
        $date = Tool::gmt();
        $data = $method . "\n" . '' . "\n" . 'text/xml' . "\n" . $date . "\n" . 'x-mns-version:2015-06-06' . "\n" .  $resource;
        $hash = base64_encode(hash_hmac('sha1', $data, $accessKeySecret, true));
        $Authorization = 'MNS ' . $accessKeyId . ':' . $hash;
        return $Authorization;
    }
    public function error() : string
    {
        return $this->error;
    }
}