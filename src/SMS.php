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
    private $template =
        '<?xml version="1.0" encoding="utf-8"?>
            <Message xmlns="http://mns.aliyuncs.com/doc/v1/">
            <MessageBody>content</MessageBody>
            <MessageAttributes>
                <DirectSMS>%s</DirectSMS>
            </MessageAttributes>
        </Message>';

    public function __construct(Array $config = null)
    {
        if($config)
        {
            foreach($config as $key => $val)
                $this->$key = $val;
        }
        else
        {
            $config = new Config();
            foreach($config('sms', []) as $key => $val)
                $this->$key = $val;
        }
        $this->directSMS['FreeSignName'] = $this->freeSignName;
        $this->directSMS['TemplateCode'] = $this->templateCode;
        $this->directSMS['Type'] = $this->type;
    }
    public function init(Array $config)
    {
        $this->accessKeyId = $config['accessKeyId'] ?? $this->accessKeyId;
        $this->accessKeySecret = $config['accessKeySecret'] ?? $this->accessKeySecret;
        $this->freeSignName = $this->directSMS['FreeSignName'] = $config['freeSignName'] ?? $this->freeSignName;
        $this->templateCode = $this->directSMS['TemplateCode'] = $config['templateCode'] ?? $this->templateCode;
        $this->type = $this->directSMS['Type'] = $config['type'] ?? $this->type;
        $this->endPoint = $config['endPoint'] ?? $this->endPoint;
        $this->topic = $config['topic'] ?? $this->topic;
        return $this;
    }
    public function to($phone)
    {
        $this->directSMS['Receiver'] = is_array($phone) ? implode(',', $phone) : $phone;
        return $this;
    }
    public function data(Array $data, $setReceiver = true)
    {
        if(is_array(current($data)))
        {
            if($this->type != 'multiContent')
            {
//                throw new \Exception('this SMS type is not multiContent');
                echo 'this SMS type is not multiContent';
                exit(1);
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
        ])->receive('xml')->data($data)->post();
    }
    public function sign($method, $resource)
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
}