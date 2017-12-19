<?php
namespace Muyu;

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

    public function __construct($url = null)
    {
        $this->url = $url;
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    }
    public function url($url)
    {
        $this->url = $url;
        return $this;
    }
    public function fullUrl($url = null)
    {
        if($url)
        {
            $this->url = $url;
            return $this;
        }
        else
            return $this->url . $this->path . ( $this->query ? '?' . http_build_query($this->query) : '');
    }
    public function path($path)
    {
        $this->path = $path;
        return $this;
    }
    public function query(Array $query)
    {
        $this->query = $query;
        return $this;
    }
    public function receive($responseType)
    {
        switch($responseType)
        {
            case 'text' : $this->responseType = 'text/plain';break;
            case 'html' : $this->responseType = 'text/html';break;
            case 'json' : $this->responseType = 'application/json';break;
            case 'xml'  : $this->responseType = 'application/xml';break;
            case 'jpg'  : $this->responseType = 'image/jpeg';break;
            case 'png'  : $this->responseType = 'image/png';break;
            case 'gif'  : $this->responseType = 'image/gif';break;
            case 'bmp'  : $this->responseType = 'image/bmp';break;
            case 'webp' : $this->responseType = 'image/webp';break;
            default     : $this->responseType = $responseType;
        }
        return $this;
    }
    public function contentType($contentType = null)
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
    public function data($data)
    {
        $this->data = is_array($data) ? http_build_query($data) : $data;
        return $this;
    }
    public function file($file)
    {
        $this->file = $file;
        return $this;
    }
    public function cookie(Array $cookie = null)
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
    public function header(Array $header = null)
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
    public function status()
    {
        return $this->result['header']['Status'];
    }
    public function content()
    {
        return $this->result['content'];
    }
    public function get($returnResult = true)
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
    public function post($returnResult = true)
    {
        $this->mothod = 'POST';
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_POST, 1);
        if($this->data)
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);
        if($this->file)
        {
            $stream = fopen($this->file, 'r');
            $size = filesize($this->file);
            curl_setopt($this->curl, CURLOPT_INFILE, $stream);
            curl_setopt($this->curl, CURLOPT_INFILESIZE, $size);
        }
        if($returnResult)
            return $this->format($this->handle($this->curl));
        else
        {
            $this->handle($this->curl);
            return $this;
        }
    }
    public function put($returnResult = true)
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
    public function delete($returnResult = true)
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
    public function patch($returnResult = true)
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
    private function format($raw)
    {
        $contentType = $this->contentType ?? $this->contentType() ?? null;
        if($contentType)
            header('Content-Type: ' . $contentType);
        switch ($this->responseType)
        {
            case 'application/json': return json_decode($raw, true);
            case 'application/xml' : return XML::parse($raw);
            case 'text/plain'      :
            case 'text/html'       :
            case 'image/jpeg'      :
            case 'image/png'       :
            case 'image/gif'       :
            case 'image/bmp'       :
            case 'image/webp'      :
            default                : return $raw;
        }
    }
    private function handle($curl)
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
                    if($key == 'HTTP/1.1 100 Continue')
                        $continue = true;
                    $headers['Status'] = $key;
                    continue;
                }
                if($continue)
                    $continue = false;
                else
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
    public function close()
    {
        curl_close($this->curl);
    }
}


//public function do($url, $type=null, $res=null, $data=null, $header=null, $useCookie=null, $cookie=null)
//    {
//        $curl = curl_init();
//        curl_setopt($curl,CURLOPT_URL,$url);
//        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
//        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
//        if($type == 'post')
//        {
//            curl_setopt($curl,CURLOPT_POST,1);
//            if(is_string($data))
//                curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
//            else
//            curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($data));
//        }
//        else if($type == 'put')
//        {
//            curl_setopt($curl,CURLOPT_PUT,1);
//            curl_setopt($curl,CURLOPT_INFILE, $data['content']);
//            curl_setopt($curl,CURLOPT_INFILESIZE ,$data['size']);
//        }
//        else if($type == 'delete')
//        {
//            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
//        }
//        if($header)
//        {
//            $headerData = [];
//            foreach($header as $key => $value)
//                array_push($headerData,$key.': ' . $value);
//            curl_setopt($curl,CURLOPT_HTTPHEADER,$headerData);
//        }
//        if($useCookie == 'get')
//        {
//            curl_setopt($curl,CURLOPT_HEADER,1);
//            $content = curl_exec($curl);
//            curl_close($curl);
//            preg_match('/Set-Cookie:(.*);/iU',$content,$str);
//            $cookie = $str[1];
//            $content = explode("\r\n", $content);
//            $body = $content[count($content)-1];
//            return $res == 'json' ? [$cookie,json_encode($body)] : [$cookie,$body];
//        }
//        else if($useCookie == 'with')
//            curl_setopt($curl,CURLOPT_COOKIE,$cookie);
//        if($res == 'header')
//        {
//            curl_setopt($curl,CURLOPT_HEADER,1);
//            $content = curl_exec($curl);
//            curl_close($curl);
//            return explode("\r\n", $content)[0];
//        }
//        $response = curl_exec($curl);
//        curl_close($curl);
//        switch ($res)
//        {
//            case 'json': return json_decode($response,true);
//            case 'xml' : return XML::parse($response);
//            case 'jpg' : header('content-type: image/jpeg');return $response;
//            default    : return $response;
//        }
//  }