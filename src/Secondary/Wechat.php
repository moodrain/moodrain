<?php
namespace Muyu\Secondary;
use Muyu\Config;
use Muyu\Curl;
use Muyu\Support\Traits\MuyuExceptionTrait;
use Muyu\Support\ApiUrl;
use function Muyu\Support\Fun\conf;
use Muyu\Support\XML;

class Wechat
{
/*
$wechat = new Wechat('wechat.test');
$wechat->addMsgHandler(function($msg) use ($wechat) {
    if($wechat->isResponsed())
        return;
    echo $wechat->responseTextMsg($wechat->isSubscribeEvent() ? 'hello' : 'emm');
});
$wechat->response();
*/
/*
$wechat = new Wechat('wechat.test');
if(isset($_GET['code'])) {
    $data = $wechat->getUserAccessToken();
    dd($wechat->getUserInfo($data['openid'], $data['access_token']));
}
$wechat->getUserCode(true);
// 启用此功能需要在微信测试号设置： 网页帐号	网页授权获取用户基本信息	无上限	修改，加上域名。
 */
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
    private $isResponsed = false;

    use MuyuExceptionTrait;
    function __construct($muyuConfig = 'wechat.default', $init = true) {
        $this->initError();
        $this->muyuConfig = $muyuConfig;
        if($init)
            $this->init(conf($muyuConfig));
    }
    function init($config) {
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
    function response() {
        foreach($this->handler as $handler)
            $handler($this->receiveData);
    }
    function addMsgHandler(callable $handler) {
        $this->handler[] = $handler;
    }
    function responseTextMsg($content) {
        if($this->isResponsed) {
            $this->addError(1, 'try to response a message twice');
            return false;
        }
        if(!$this->receiveData)
            $this->receive();
        $data = $this->receiveData;
        $responseStr = sprintf($this->template, $data['fromUserName'], $data['toUserName'], time(), 'text', $content);
        $this->isResponsed = true;
        return $responseStr;
    }
    function getAccessToken() {
        $config = new Config();
        $accessToken = $config($this->muyuConfig . '.accessToken', null);
        $expire = $config($this->muyuConfig . '.expire', null);
        if(!$accessToken || time() > $expire) {
            $curl = new Curl();
            $data = $curl->url(ApiUrl::$urls['wxToken'])->query([
                'grant_type' => 'client_credential',
                'appid' => $this->appId,
                'secret' => $this->appSecret,
            ])->accept('json')->get();
            if(!$data) {
                $this->addError(1, 'request error', $curl->error());
                return false;
            }
            $success = true;
            if(isset($data['errcode']) && $data['errcode'] === -1) {
                $success = false;
                $try = 10;
                while ($try-- > 0) {
                    $data = $curl->get();
                    if($data && $data['errcode'] === 0) {
                        $success = true;
                        break;
                    }
                    else if($data && $data['errcode'] === -1)
                        continue;
                    else
                        $this->addError(1, 'request error', $curl->error());
                }
            }
            if(!$success) {
                $this->addError(2, 'wechat server busy');
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
    function getUserCode($needInfo = false) {
        if(!$this->getUserAccessTokenUrl) {
            $this->addError(3, 'getUserAccessTokenUrl not set');
            echo 'getUserAccessTokenUrl not set';
            return;
        }
        $redirectUrl = urlencode($this->getUserAccessTokenUrl);
        $scode = $needInfo ? 'snsapi_userinfo' : 'snsapi_base';
        header("Location: '" . ApiUrl::$urls['wxUserCode'] . "'?appid={$this->appId}&redirect_uri={$redirectUrl}&response_type=code&scope={$scode}&state=muyuchengfeng#wechat_redirect");
    }
    function getUserAccessToken() {
        $code = $_GET['code'] ?? null;
        $curl = new Curl();
        $data = $curl->url(ApiUrl::$urls['wxUserToken'])->query([
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'code' => $code,
            'grant_type' => 'authorization_code',
        ])->accept('json')->get();
        if(isset($data['errcode']) && $data['errcode'] !== 0) {
            $this->addError(4, 'api error', null, $data['errcode'] . ':' . $data['errmsg']);
            return false;
        }
        return $data;
    }
    function getUserInfo($openId, $userAccessToken) {
        $curl = new Curl();
        $data = $curl->url('https://api.weixin.qq.com/sns/userinfo')->query([
            'access_token' => $userAccessToken,
            'openid' => $openId,
            'lang' => 'zh_CN',
        ])->accept('json')->get();
        if(isset($data['errcode']) && $data['errcode'] !== 0) {
            $this->addError(4, 'api error', null, $data['errcode'] . ':' . $data['errmsg']);
            return false;
        }
        return $data;
    }
    function authServer() {
        echo $_GET['echostr'];
        exit();
    }
    function receiveData($key = null) {
        return $key ? $this->receiveData[$key] : $this->receiveData;
    }
    function msgHandler() {
        return $this->handler;
    }
    function isResponsed() {
        return $this->isResponsed;
    }
    function isSubscribeEvent() {
        return $this->receiveData['msgType'] == 'event' && $this->receiveData['event'] == 'subscribe';
    }
    function isUnSubscribeEvent() {
        return $this->receiveData['msgType'] == 'event' && $this->receiveData['event'] == 'unsubscribe';
    }
    function isTextMsg() {
        return $this->receiveData['msgType'] == 'text';
    }
    function receive() {
        if(!$this->check()) {
            $this->addError(5, 'check not pass');
            return [];
        }
        $data = XML::parse(file_get_contents("php://input"));
        $new = [];
        foreach($data as $key => $val)
            $new[lcfirst($key)] = $val;
        $this->receiveData = $new;
        $this->fromUserName = $this->receiveData['fromUserName'];
        $this->toUserName = $this->receiveData['toUserName'];
        return $new;
    }
    function rawReceiveData() {
        if(!$this->check()) {
            $this->addError(5, 'check not pass');
            return null;
        }
        return file_get_contents("php://input");
    }
    function check() {
        $signature = $_GET["signature"] ?? null;
        $timestamp = $_GET["timestamp"] ?? null;
        $nonce = $_GET["nonce"] ?? null;
        $tmpArr = [$this->token, $timestamp, $nonce];
        sort($tmpArr, SORT_STRING);
        return sha1(implode($tmpArr)) == $signature;
    }
}