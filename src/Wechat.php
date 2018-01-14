<?php
namespace Muyu;
class Wechat
{
    private $appId;
    private $appSecret;
    private $token;
    private $encodingAESKey;
    private $template;

    private $fromUserName;
    private $toUserName;
    private $receiveData;
    private $handler = [];
    private $isResponse = false;


    public function response()
    {
        foreach($this->handler as $handler)
            $handler($this->receiveData);
    }
    public function addHandler(callable $handler)
    {
        $this->handler[] = $handler;
    }
    public function responseTextMsg(string $content)
    {
        if($this->isResponse)
            Tool::log('尝试多次回复同一条消息' . $this->rawReceiveData());
        $data = $this->receiveData;
        $responseStr = sprintf($this->template, $data['fromUserName'], $data['toUserName'], time(), 'text', $content);
        $this->isResponse = true;
        return $responseStr;
    }

    public function __construct()
    {
        $config = new Config();
        $config = $config('wechat');
        foreach ($config as $key => $val)
            $this->$key = $val;
        if(!$this->check())
            exit();
        $this->receiveData = $this->receive();
        $this->fromUserName = $this->receiveData['fromUserName'];
        $this->toUserName = $this->receiveData['toUserName'];
        $this->template =
        "<xml>
            <ToUserName><![CDATA[%s]]></ToUserName>
            <FromUserName><![CDATA[%s]]></FromUserName>
            <CreateTime>%s</CreateTime>
            <MsgType><![CDATA[%s]]></MsgType>
            <Content><![CDATA[%s]]></Content>
            <FuncFlag>0</FuncFlag>
        </xml>";
    }
    public function auth()
    {
        echo $_GET['echostr'];
        exit();
    }
    public function receiveData(string $key = null)
    {
        return $key ? $this->receiveData[$key] : $this->receiveData;
    }
    public function rawReceiveData()
    {
        return file_get_contents("php://input");
    }
    public function handler()
    {
        return $this->handler;
    }
    public function isResponse()
    {
        return $this->isResponse;
    }
    public function isSubscribe()
    {
        if($this->receiveData['msgType'] == 'event' && $this->receiveData['event'] == 'subscribe')
            return true;
    }
    public function isUnSubscribe()
    {
        if($this->receiveData['msgType'] == 'event' && $this->receiveData['event'] == 'unsubscribe')
            return true;
    }
    public function isText()
    {
        return $this->receiveData['msgType'] == 'text';
    }
    private function check()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
        $tmpArr = [$this->token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        return sha1(implode($tmpArr)) == $signature;
    }
    private function receive()
    {
        $data = XML::parse(file_get_contents("php://input"));
        $new = [];
        foreach($data as $key => $val)
            $new[lcfirst($key)] = $val;
        return $new;
    }
}