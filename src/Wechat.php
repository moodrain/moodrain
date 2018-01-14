<?php
namespace Muyu;
class Wechat
{
    private $appId;
    private $appSecret;
    private $token;
    private $encodingAESKey;
    private $template;
    private $muyuConfig;
    private $getUserAccessTokenUrl;

    private $fromUserName;
    private $toUserName;
    private $receiveData;
    private $handler = [];
    private $isResponse = false;

    private $error;

    public function response()
    {
        foreach($this->handler as $handler)
            $handler($this->receiveData);
    }
    public function addMsgHandler(callable $handler)
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
    public function getAccessToken()
    {
        $config = new Config();
        $accessToken = $config('wechat.accessToken', null);
        $expire = $config('wechat.expire', null);
        if(!$accessToken || time() > $expire)
        {
            $curl = new Curl();
            $data = $curl->url('https://api.weixin.qq.com/cgi-bin/token')->query([
                'grant_type' => 'client_credential',
                'appid' => $this->appId,
                'secret' => $this->appSecret,
            ])->receive('json')->get();
            if(isset($data['errcode']))
            {
                $try = 10;
                if($data['errcode'] == -1)
                {
                    $pass = false;
                    while($pass)
                    {
                        sleep(1);
                        $data = $curl->get();
                        $pass = !isset($data['errcode']);
                        if($try-- <= 0)
                        {
                            $this->error = 'wechat server busy';
                            break;
                        }
                    }
                }
                else
                    $this->error = $data;
                return false;
            }
            $accessToken = $data['access_token'];
            $expire = $data['expires_in'] + time() - 300;
            $config->reset($this->muyuConfig . '.accessToken', $accessToken);
            $config->reset($this->muyuConfig . '.expire', $expire);
            $config->modify($this->muyuConfig . '.accessToken', $accessToken);
            $config->modify($this->muyuConfig . '.expire', $expire);
        }
        return $accessToken;
    }
    public function getUserCode($subscribe = true)
    {
        if(!$this->getUserAccessTokenUrl)
        {
            echo 'getUserAccessTokenUrl not set';
            exit();
        }
        $redirectUrl = urlencode($this->getUserAccessTokenUrl);
        $scode = $subscribe ? 'snsapi_base' : 'snsapi_userinfo';
        header("Location: https://open.weixin.qq.com/connect/oauth2/authorize?appid={$this->appId}&redirect_uri={$redirectUrl}&response_type=code&scope=snsapi_base&state=muyuchengfeng#wechat_redirect");
    }
    public function getUserAccessToken()
    {
        $code = $_GET['code'] ?? null;
        $curl = new Curl();
        $data = $curl->url('https://api.weixin.qq.com/sns/oauth2/access_token')->query([
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ])->receive('json')->get();
        if(isset($data['errcode']))
        {
            $this->error = $data['errcode'] . $data['errmsg'];
            return false;
        }
        return $data;
    }
    public function getUserInfo($openId, $userAccessToken)
    {
        $curl = new Curl();
        $data = $curl->url('https://api.weixin.qq.com/sns/userinfo')->query([
            'access_token' => $userAccessToken,
            'openid' => $openId,
            'lang' => 'zh_CN',
        ])->receive('json')->get();
        if(isset($data['errcode']))
        {
            $this->error = $data['errcode'] . $data['errmsg'];
            return false;
        }
        return $data;
    }
    public function __construct(string $muyuConfig = 'wechat')
    {
        $this->muyuConfig = $muyuConfig;
        $config = new Config();
        $config = $config($this->muyuConfig);
        foreach ($config as $key => $val)
            $this->$key = $val;
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
    public function init(array $config)
    {
        foreach ($config as $key => $val)
            $this->key = $val;
    }
    public function muyuConfig(string $path = null)
    {
        if($path)
            $this->muyuConfig = $path;
        else
            return $this->muyuConfig;
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
    public function msgHandler()
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
        $signature = $_GET["signature"] ?? null;
        $timestamp = $_GET["timestamp"] ?? null;
        $nonce = $_GET["nonce"] ?? null;
        $tmpArr = [$this->token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        if(!sha1(implode($tmpArr)) == $signature)
            exit();
    }
    public function receive()
    {
        $this->check();
        $data = XML::parse(file_get_contents("php://input"));
        $new = [];
        foreach($data as $key => $val)
            $new[lcfirst($key)] = $val;
        $this->receiveData = $new;
        $this->fromUserName = $this->receiveData['fromUserName'];
        $this->toUserName = $this->receiveData['toUserName'];
        return $new;
    }
    public function rawReceiveData()
    {
        $this->check();
        return file_get_contents("php://input");
    }
    public function error()
    {
        return $this->error;
    }
}