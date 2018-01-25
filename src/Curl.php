<?php
namespace Muyu;

use Muyu\Support\XML;

class Curl
{
    private $curl;
    private $url;
    private $path;
    private $query;
    private $mothod;
    private $data;
    private $file;
    private $contentType;
    private $result;
    private $responseType;
    private $transfer;

    public function __construct(string $url = null)
    {
        $this->url = $url;
        $this->curl = curl_init();
        $this->transfer = true;
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, 1);
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
        else
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
    public function receive(string $responseType) : Curl
    {
        switch($responseType)
        {
            case 'text' : $this->responseType = 'text/plain';break;
            case 'html' : $this->responseType = 'text/html';break;
            case 'json' : $this->responseType = 'application/json';break;
            case 'xml'  : $this->responseType = 'application/xml';break;
            case 'pdf'  : $this->responseType = 'application/pdf';break;
            case 'zip'  : $this->responseType = 'application/zip';break;
            case 'jpg'  : $this->responseType = 'image/jpeg';break;
            case 'png'  : $this->responseType = 'image/png';break;
            case 'gif'  : $this->responseType = 'image/gif';break;
            case 'bmp'  : $this->responseType = 'image/bmp';break;
            case 'webp' : $this->responseType = 'image/webp';break;
            default     : $this->responseType = $responseType;
        }
        return $this;
    }
    public function transfer(bool $transfer = null)
    {
        if($transfer !== null)
        {
            $this->transfer = $transfer;
            return $this;
        }
        else
            return $this->transfer;
    }
    public function contentType(string $contentType = null) : Curl
    {
        if($contentType)
        {
            $this->contentType = $contentType;
            $this->header(['Content-Type' => $contentType]);
            return $this;
        }
        else
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
        else
            return $this->result['header'];
    }
    public function status() : string
    {
        return $this->result['header']['Status'];
    }
    public function content() : string
    {
        return $this->result['content'];
    }
    public function get(bool $returnResult = true)
    {
        $this->mothod = 'GET';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        if($returnResult)
            return $this->format($this->handle($this->curl));
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function post(bool $returnResult = true)
    {
        $this->mothod = 'POST';
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
            return $this->format($this->handle($this->curl));
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function put(bool $returnResult = true)
    {
        $this->mothod = 'PUT';
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
            return $this->format($this->handle($this->curl));
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function delete(bool $returnResult = true)
    {
        $this->mothod = 'DELETE';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if($returnResult)
            return $this->format($this->handle($this->curl));
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function patch(bool $returnResult = true)
    {
        $this->mothod = 'PATCH';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        if($returnResult)
            return $this->format($this->handle($this->curl));
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    private function format(string $raw)
    {
        if($this->responseType)
            header('Content-Type: ' . $this->responseType);
        if($this->transfer)
        {
            if(in_array($this->responseType, [
                'application/json',
                'application/xml',]))
                header('Content-Type: text/plain');
            switch ($this->responseType)
            {
                case 'application/json': return json_decode($raw, true);
                case 'application/xml' : return XML::parse($raw);
                default                : return $raw;
            }
        }
        else
            return $raw;
    }
    private function handle($curl) : string
    {
        curl_setopt($curl, CURLOPT_HEADER, 1);
        $content = curl_exec($curl);
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
        if(!isset($headers['Status']))
            return null;
        $this->result['header'] = $headers;
        $this->result['content'] = $content;
        return $this->content();
    }
    public function close() : void
    {
        curl_close($this->curl);
    }
    public function __destruct()
    {
        $this->close();
    }
}