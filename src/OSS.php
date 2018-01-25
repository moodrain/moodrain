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
    private $prefix;
    private $authorization = '';
    private $pubKeyUrl = '';
    private $isVerified = false;

    public function __construct(string $muyuConfig = 'oss.default', bool $init = true)
    {
        $config = new Config();
        if($init)
            $this->init($config($muyuConfig));
    }
    public function init(array $config)
    {
        foreach ($config as $key => $val)
            $this->$key = $val;
        return $this;
    }
    public function policy(string $dir, string $callback, array $data = null) : string
    {
        $response = $this->getPolicy($dir, $callback, $data);
        $response = json_encode(['code' => 200, 'msg' => '获取policy成功', 'data' => $response], JSON_UNESCAPED_UNICODE);
        if($this->cors)
            header('Access-Control-Allow-Origin: ', $this->cors);
        return $response;
    }
    public function fileInfo() : array
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
    public function callback(callable $callback) : string
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
        $response = $callback($this->fileInfo());
        return $this->response($response);
    }
    public function prefix(string $prefix = null)
    {
        if(!$prefix)
            return $this->prefix;
        else
        {
            $this->prefix = $prefix;
            return $this;
        }
    }
    public function get(string $file, string $receive = 'text', string $query = null)
    {
        $resource = '/' . $this->bucketName . '/' . ($this->prefix ? $this->prefix . '/' : '') . $file . $query;
        $url = $this->domain . '/' . ($this->prefix ? $this->prefix . '/' : '') . $file . $query;
        return (new Curl())->url($url)->receive($receive)->header([
            'Date' => Tool::gmt(),
            'Authorization' => $this->sign('get', $resource),
        ])->get();
    }
    public function put(string $from, string $to, string $contentType = null)
    {
        $resource = '/' . $this->bucketName . '/' . ($this->prefix ? $this->prefix . '/' : '') .  $to;
        $url = $this->domain . '/' . ($this->prefix ? $this->prefix . '/' : '') . $to;
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
                default     : $contentType = 'application/octet-stream';
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
    public function del(string $file)
    {
        $resource = '/' . $this->bucketName . '/' . ($this->prefix ? $this->prefix . '/' : '') . $file;
        $url = $this->domain . '/' . ($this->prefix ? $this->prefix . '/' : '') . $file;
        $rs = (new Curl())->url($url)->header([
            'Date' => Tool::gmt(),
            'Authorization' => $this->sign('delete', $resource),
        ])->delete(false);
        return $rs->status() == 'HTTP/1.1 204 No Content' ? true : $rs->content();
    }
    public function list(string $prefix = null) : array
    {
        $resource = '/' . $this->bucketName . '/';
        $url = $this->domain;
        $prefix = ($prefix && $prefix != '/' ? $prefix : null) ?? $this->prefix;
        $maxKeys = 1000;
        $curl = new Curl();
        $curl->url($url)->receive('xml')->header([
            'Date' => Tool::gmt(),
            'Authorization' => $this->sign('get', $resource),
        ])->query([
            'prefix' => $prefix,
            'max-keys' => $maxKeys,
        ]);
        $finish = false;
        $trials = 10;
        $list = [];
        while(!($finish || $trials <= 0))
        {
            $result = $curl->get();
            if(!$result)
            {
                $trials--;
                if($trials == 0)
                    return null;
                continue;
            }
            if($result['IsTruncated'] == 'true')
                $curl->query([
                    'prefix' => $prefix,
                    'max-keys' => $maxKeys,
                    'marker' => $result['NextMarker'],
                ]);
            else if($result['IsTruncated'] == 'false')
                $finish = true;
            if(!isset($result['Contents']))
                return [];
            $list = array_merge($list, $result['Contents']);
        }
        return $list;
    }
    private function getPolicy(string $dir, string $callback, array $data = null) : array
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
    private function verify() : void
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
    private function response(array $response) : string
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
    private function sign(string $method, string $resource, string $contentType = null, string $contentMd5 = null) : string
    {
        $method = strtoupper($method);
        $signature = base64_encode(hash_hmac('sha1', $method . "\n" . $contentMd5 . "\n" . $contentType . "\n" . Tool::gmt() . "\n" . $resource, $this->accessKeySecret, true));
        $authorization = 'OSS ' . $this->accessKeyId . ':' . $signature;
        return $authorization;
    }
}