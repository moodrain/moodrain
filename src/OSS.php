<?php
namespace Muyu;

class OSS
{
    private $accessKeyId;
    private $accessKeySecret;
    private $address;
    private $domain;
    private $bucketName;
    private $endPoint;
    private $policy;
    private $callback;
    private $expire;
    private $cors;

    private $authorization = '';
    private $pubKeyUrl = '';
    private $isVerified = false;

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
            foreach($config('oss', []) as $key => $val)
                $this->$key = $val;
        }
    }
    public function init(Array $config)
    {
        $this->address = $config['address'] ?? $this->address;
        $this->domain = $config['domain'] ?? $this->domain;
        $this->bucketName = $config['bucketName'] ?? $this->bucketName;
        $this->endPoint = $config['endPoint'] ?? $this->endPoint;
        $this->policy = $config['policy'] ?? $this->policy;
        $this->callback = $config['callback'] ?? $this->callback;
        $this->expire = $config['expire'] ?? $this->expire;
        $this->cors = $config['cors'] ?? $this->cors;
    }
    public function policy($dir, $callback, $data = null)
    {
        $response = $this->getPolicy($dir, $callback, $data);
        $response = json_encode(['code' => 200, 'msg' => '获取policy成功', 'data' => $response], JSON_UNESCAPED_UNICODE);
        if($this->cors)
            header('Access-Control-Allow-Origin: ', $this->cors);
        return $response;
    }
    public function getPolicy($dir, $callback, $data = null)
    {
        $end = time() + $this->expire;
        $expiration = Tool::gmt_iso8601($end);
        $condition = ['content-length-range', 0, 1048576000];
        $conditions[] = $condition;
        $start = array(0=>'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;
        $arr = array('expiration' => $expiration,'conditions' => $conditions);
        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $this->accessKeySecret, true));
        $callback_param = array
        (
            'callbackUrl' =>  $callback,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}&data=${x:data}',
            'callbackBodyType' => "application/x-www-form-urlencoded"
        );
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        $response = array();
        $response['accessKeyId'] = $this->accessKeyId;
        $response['address'] = $this->address;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['dir'] = $dir;
        $response['callback'] = $base64_callback_body;
        $response['data'] = $data;
        return $response;
    }
    public function callback(\Closure $callback)
    {
        try{
            $this->authorization = base64_decode($_SERVER['HTTP_AUTHORIZATION']);
            $this->pubKeyUrl = base64_decode($_SERVER['HTTP_X_OSS_PUB_KEY_URL']);
        } catch (\Exception $e)
        {
            header("HTTP/1.1 403 Forbidden");
            exit();
        }
        $this->verify();
        $response = $callback($this->getFileInfo());
        return $this->response($response);
    }
    public function verify()
    {
        $authorization = $this->authorization;
        $pubKeyUrl = $this->pubKeyUrl;
        $pubKey = (new Curl())->url($pubKeyUrl)->get();
        if(!$pubKey)
        {
            header("HTTP/1.1 403 Forbidden");
            exit();
        }
        $body = file_get_contents('php://input');
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');
        if ($pos === false)
            $authStr = urldecode($path)."\n".$body;
        else
            $authStr = urldecode(substr($path, 0, $pos)).substr($path, $pos, strlen($path) - $pos)."\n".$body;
        if(openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5) == 1)
            $this->isVerified = true;
        else
        {
            header("HTTP/1.1 403 Forbidden");
            exit();
        }
    }
    public function response($response)
    {
        $response = isset($response) ? $response : ['code' => 200, 'msg' => 'success'];
        if($this->isVerified)
            return json_encode($response, JSON_UNESCAPED_UNICODE);
        else
        {
            header("http/1.1 403 Forbidden");
            exit();
        }
    }
    public function getFileInfo()
    {
        $rawData = file_get_contents('php://input');
        $rawData = explode('&', $rawData);
        $fileInfo = [];
        foreach($rawData as $r)
        {
            $r = explode('=', $r);
            $fileInfo[$r[0]] = $r[1];
        }
        $fileInfo['filename'] = urldecode($fileInfo['filename']);
        $fileInfo['mimeType'] = urldecode($fileInfo['mimeType']);
        if(isset($fileInfo['data']))
            $fileInfo['data'] = json_decode(urldecode($fileInfo['data']),true);
        return $fileInfo;
    }
    public function sign($method, $resource, $contentType = '', $contentMd5 = '')
    {
        $method = strtoupper($method);
        $signature = base64_encode(hash_hmac('sha1', $method . "\n" . $contentMd5 . "\n" . $contentType . "\n" . Tool::gmt() . "\n" . $resource, $this->accessKeySecret, true));
        $authorization = 'OSS ' . $this->accessKeyId . ':' . $signature;
        return $authorization;
    }
    public function get($file, $responseType = 'text', $query = '')
    {
        $resource = '/' . $this->bucketName . '/' . $file . $query;
        $url = $this->domain . '/' . $file . $query;
        return (new Curl())->url($url)->receive($responseType)->header([
            'Date' => Tool::gmt(),
            'Authorization' => $this->sign('get', $resource),
        ])->get();
    }
    public function put($from, $to, $contentType = null)
    {
        $resource = '/' . $this->bucketName . '/' . $to;
        $url = $this->domain . '/' . $to;
        $contentMd5 = base64_encode(md5_file($from, true));
        if(!$contentType)
        {
            switch (Tool::ext($from))
            {
                case 'txt'  : $contentType = 'text/plain';break;
                case 'html' : $contentType = 'text/html';break;
                case 'xml'  : $contentType = 'application/xml';break;
                case 'json' : $contentType = 'application/json';break;
                case 'zip'  : $contentType = 'application/zip';break;
                case 'jpg'  : $contentType = 'image/jpeg';break;
                case 'png'  : $contentType = 'image/png';break;
                case 'gif'  : $contentType = 'image/gif';break;
                case 'bmp'  : $contentType = 'image/bmp';break;
                case 'webp' : $contentType = 'image/webp';break;
                default     : $contentType = null;
            }
        }
        $rs = (new Curl())->url($url)->file($from)->contentType($contentType)->header([
            'Date' => Tool::gmt(),
            'Authorization' => $this->sign('put', $resource, $contentType, $contentMd5),
            'Content-Length' => filesize($from),
            'Content-Type' => $contentType,
            'Content-MD5' => $contentMd5,
         ])->put(false);
        return $rs->status() == 'HTTP/1.1 200 OK' ? true : $rs->content();
    }
    public function del($file)
    {
        $resource = '/' . $this->bucketName . '/' . $file;
        $url = $this->domain . '/' . $file;
        $rs = (new Curl())->url($url)->header([
            'Date' => Tool::gmt(),
            'Authorization' => $this->sign('delete', $resource),
        ])->delete(false);
        return $rs->status() == 'HTTP/1.1 204 No Content' ? true : $rs->content();
    }
}