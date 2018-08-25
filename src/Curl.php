<?php
namespace Muyu;

use Muyu\Support\HttpStatus;
use Muyu\Support\XML;

class Curl
{
    private $curl;
    private $url;
    private $path;
    private $query;
    private $method;
    private $data;
    private $file;
    private $contentType;
    private $accept;
    private $result;
    private $transfer;
    private $timeout;
    private $retry;
    private $retryErrorCode;
    private $proxy;
    private $error = '';

    public function __construct(string $url = null)
    {
        $this->url = $url;
        $this->transfer = true;
        $this->timeout = 60;
        $this->retryErrorCode = [28, 52];
        $this->proxy = false;
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_HEADER, 1);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 60);
        curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, 60);
    }
    public function url(string $url) : Curl
    {
        $this->url = $url;
        return $this;
    }
    public function fullUrl(string $url = null)
    {
        if($url)
        {
            $this->url = $url;
            return $this;
        }
        return $this->url . $this->path . ( $this->query ? '?' . http_build_query($this->query) : '');
    }
    public function path(string $path) : Curl
    {
        $this->path = $path;
        return $this;
    }
    public function query(array $query) : Curl
    {
        $this->query = $query;
        return $this;
    }
    public function transfer(bool $transfer = null)
    {
        if($transfer !== null)
        {
            $this->transfer = $transfer;
            return $this;
        }
        return $this->transfer;
    }
    public function accept(string $accept = null)
    {
        if($accept !== null)
        {
            $this->accept = $accept;
            return $this;
        }
        return $this->accept;
    }
    public function contentType()
    {
        return $this->contentType;
    }
    public function data(array $data) : Curl
    {
        $this->data = $data;
        return $this;
    }
    public function file($file) : Curl
    {
        $this->file = $file;
        return $this;
    }
    public function string(string $data) : Curl
    {
        $this->data = $data;
        return $this;
    }
    public function cookie(array $cookie = null)
    {
        if($cookie)
        {
            $cookieStr = '';
            foreach($cookie as $key => $val)
                $cookieStr .= $key . '=' . $val . ';';
            curl_setopt($this->curl, CURLOPT_COOKIE, $cookieStr);
            return $this;
        }
        else
        {
            $cookie = [];
            foreach($this->result['header']['Set-Cookie'] as $info)
            {
                $info = explode(';', $info)[0];
                $key = explode('=', $info)[0];
                $val = explode('=', $info)[1];
                $cookie[$key] = $val;
            }
            return $cookie;
        }
    }
    public function header(array $header = null)
    {
        if($header)
        {
            $headerData = [];
            foreach($header as $key => $value)
                array_push($headerData, $key . ': ' . $value);
            curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headerData);
            return $this;
        }
        return $this->result['header'];
    }
    public function status() : string
    {
        return $this->result['header']['Status'] ?? '';
    }
    public function title() : string
    {
        return Tool::strBetween($this->content(), '<title>', '</title>');
    }
    public function content()
    {
        return $this->result['content'] ?? '';
    }
    public function timeout(int $second = null)
    {
        if($second)
        {
            $this->timeout = $second;
            curl_setopt($this->curl, CURLOPT_TIMEOUT, $second);
            curl_setopt($this->curl, CURLOPT_CONNECTTIMEOUT, $second);
            return $this;
        }
        else
            return $this->timeout;
    }
    public function retry(int $times = null)
    {
        if($times)
        {
            $this->retry = $times;
            return $this;
        }
        return $this->retry;
    }
    public function retryErrorCode($errorCode = null)
    {
        if($errorCode)
        {
            $this->retryErrorCode = is_array($errorCode) ? $errorCode : [$errorCode];
            return $this;
        }
        return count($this->retryErrorCode) == 1 ? $this->retryErrorCode[0] : $this->retryErrorCode;
    }
    public function proxy(string $proxy)
    {
        if($proxy)
        {
            $this->proxy = $proxy;
            return $this;
        }
        return $this->proxy;
    }
    public function get(bool $returnResult = true)
    {
        $this->method = 'GET';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        if($returnResult)
            return $this->handle($this->curl);
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function post(bool $returnResult = true)
    {
        $this->method = 'POST';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_POST, 1);
        if($this->data)
        {
            $data = $this->data;
            if(is_array($data))
            {
                if($this->file)
                {
                    $files = [];
                    foreach ($this->file as $key => $val)
                        $files[$key] = new \CURLFile($val);
                    $data = array_merge($this->data, $files);
                }
            }
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        }
        if($returnResult)
            return $this->handle($this->curl);
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function put(bool $returnResult = true)
    {
        $this->method = 'PUT';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_PUT, 1);
        if($this->file)
        {
            $stream = fopen($this->file, 'r');
            $size = filesize($this->file);
            curl_setopt($this->curl, CURLOPT_INFILE, $stream);
            curl_setopt($this->curl, CURLOPT_INFILESIZE, $size);
        }
        if($this->data)
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);
        if($returnResult)
            return $this->handle($this->curl);
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function delete(bool $returnResult = true)
    {
        $this->method = 'DELETE';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if($returnResult)
            return $this->handle($this->curl);
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function patch(bool $returnResult = true)
    {
        $this->method = 'PATCH';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if($returnResult)
            return $this->handle($this->curl);
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    private function format($raw)
    {
        if($raw === null || $raw === false)
            return $raw;
        if($this->transfer)
        {
            if($this->accept)
            {
                switch($this->accept)
                {
                    case 'json': return json_decode($raw, true);
                    case 'xml': return XML::parse($raw);
                    default: return $raw;
                }
            }
            else
                switch ($this->contentType)
                {
                    case 'application/json': return json_decode($raw, true);
                    case 'application/xml' : return XML::parse($raw);
                    default                : return $raw;
                }
        }
        else
            return $raw;
    }
    private function handle($curl)
    {
        if($this->proxy)
        {
            curl_setopt($this->curl, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5_HOSTNAME);
            curl_setopt($this->curl, CURLOPT_PROXY, $this->proxy);
        }
        $content = curl_exec($curl);
        $this->error = curl_errno($curl) === 0 ? '' : curl_errno($curl);
        if($this->retry)
        {
            $times = $this->retry;
            while($times-- > 0 && in_array(curl_errno($curl), $this->retryErrorCode))
                $content = curl_exec($curl);
        }
        if($content === null || $content === false)
            return $content;
        $response = explode("\r\n", $content);
        $headers = [];
        $headers['Set-Cookie'] = [];
        $content = '';
        $endHeader = false;
        $count = 0;
        $continue = false;
        $moved = false;
        foreach($response as $row)
        {
            $count++;
            if(!$endHeader)
            {
                $header = explode(': ', $row);
                $key = $header[0];
                $val = $header[1] ?? null;
                if($val == null && strlen($row) != 0)
                {
                    if(strstr($key, '100'))
                        $continue = true;
                    if(strstr($key, '301') || strstr($key, '302') || strstr($key, '307') || strstr($key, '308'))
                        $moved = true;
                    $headers['Status'] = $key;
                    continue;
                }
                if($continue)
                {
                    $continue = false;
                    continue;
                }
                if($moved && strlen($row) == 0)
                {
                    $moved = false;
                    continue;
                }
                if($moved)
                    continue;
                {
                    if(strlen($row))
                    {
                        if($key == 'Set-Cookie')
                            $headers['Set-Cookie'][] = $val;
                        else
                            $headers[$key] = $val;
                    }
                    else
                        $endHeader = true;
                }
            }
            else
                $content .= $row . ($count == count($response) ? '' : "\r\n");
        }
        $this->result['header'] = $headers;
        $this->result['body'] = $content;
        $this->result['content'] = $this->format($content);
        $this->contentType = $headers['Content-Type'] ?? '';
        return $this->content();
    }
    public function is404() : bool
    {
        return $this->status() == HttpStatus::_404();
    }
    public function ss() : Curl
    {
        $this->proxy('localhost:1080');
        return $this;
    }
    public function error()
    {
        return $this->error;
    }
    public function close() : void
    {
        if(is_resource($this->curl))
            curl_close($this->curl);
    }
    public function __destruct()
    {
        $this->close();
    }
}