<?php
namespace Muyu;

class Curl
{
    private $curl;
    private $url;
    private $query;
    private $mothod;
    private $responseType;
    private $data;
    private $file;
    private $header;
    private $result;

    public function __construct(String $url)
    {
        $this->url = $url;
        $this->curl = curl_init();
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
    }
    public function url(String $url)
    {
        $this->url = $url;
        return $this;
    }
    public function fullUrl(String $url = null)
    {
        if($url)
        {
            $this->url = $url;
            return $this;
        }
        else
            return $this->url . ( $this->query ? '?' . http_build_query($this->query) : '');
    }
    public function query(Array $query)
    {
        $this->query = $query;
        return $this;
    }
    public function receive(String $responseType)
    {
        if(!in_array($responseType, ['text', 'json', 'xml']))
            throw new \Exception ('unsupported responseType');
        $this->responseType = $responseType;
        return $this;
    }
    public function stringData(String $data)
    {
        $this->data = $data;
        return $this;
    }
    public function data(Array $data)
    {
        $this->data = http_build_query($data);
        return $this;
    }
    public function file(String $file)
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
    public function content()
    {
        return $this->result['content'];
    }

    
    public function get()
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        return $this->format($this->handle($this->curl));
    }
    public function post()
    {
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
        return $this->format($this->handle($this->curl));
    }
    public function put()
    {
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
        return $this->format($this->handle($this->curl));
    }
    public function delete()
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        return $this->format($this->handle($this->curl));
    }
    public function patch()
    {
        curl_setopt($this->curl, CURLOPT_URL, $this->fullUrl());
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        return $this->format($this->handle($this->curl));
    }

    private function format(String $raw)
    {
        switch ($this->responseType)
        {
            case 'json': return json_decode($response, true);
            case 'xml' : return Muyu\XML::parse($response);
            case 'jpg' : header('content-type: image/jpeg');return $response;
            case 'png' : header('content-type: image/png');return $response;
            case 'gif' : header('content-type: image/gif');return $response;
            case 'bmp' : header('content-type: image/bmp');return $response;
            default    : return $response;
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
        foreach($response as $row)
        {
            $count++;
            if(!$endHeader)
            {
                $header = explode(': ', $row);
                if(count($header) == 1 && strlen($row) != 0)
                {
                    $headers['Status'] = $header[0];
                    continue;
                }
                if(strlen($row))
                {
                    if($header[0] == 'Set-Cookie')
                        $headers[$header[0]][] = $header[1];
                    else
                        $headers[$header[0]] = $header[1];
                }
                else
                    $endHeader = true;
            }
            else
                $content .= $row . ($count == count($response) ? '' : "\r\n");
        }
        $this->result['header'] = $headers;
        $this->result['content'] = $content;
        return $this->content();
    }
    public function close()
    {
        curl_close($this->curl);
    }
    public function do($url, $type=null, $res=null, $data=null, $header=null, $useCookie=null, $cookie=null)
    {
        $curl = curl_init();
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
        if($type == 'post')
        {
            curl_setopt($curl,CURLOPT_POST,1);
            if(is_string($data))
                curl_setopt($curl,CURLOPT_POSTFIELDS,$data);
            else
            curl_setopt($curl,CURLOPT_POSTFIELDS,http_build_query($data));
        }
        else if($type == 'put')
        {
            curl_setopt($curl,CURLOPT_PUT,1);
            curl_setopt($curl,CURLOPT_INFILE, $data['content']);
            curl_setopt($curl,CURLOPT_INFILESIZE ,$data['size']);
        }
        else if($type == 'delete')
        {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }
        if($header)
        {
            $headerData = [];
            foreach($header as $key => $value)
                array_push($headerData,$key.': ' . $value);
            curl_setopt($curl,CURLOPT_HTTPHEADER,$headerData);
        }
        if($useCookie == 'get')
        {
            curl_setopt($curl,CURLOPT_HEADER,1);
            $content = curl_exec($curl);
            curl_close($curl);
            preg_match('/Set-Cookie:(.*);/iU',$content,$str);
            $cookie = $str[1];
            $content = explode("\r\n", $content);
            $body = $content[count($content)-1];
            return $res == 'json' ? [$cookie,json_encode($body)] : [$cookie,$body];
        }
        else if($useCookie == 'with')
            curl_setopt($curl,CURLOPT_COOKIE,$cookie);
        if($res == 'header')
        {
            curl_setopt($curl,CURLOPT_HEADER,1);
            $content = curl_exec($curl);
            curl_close($curl);
            return explode("\r\n", $content)[0];
        }
        $response = curl_exec($curl);
        curl_close($curl);
        switch ($res)
        {
            case 'json': return json_decode($response,true);
            case 'xml' : return XML::parse($response);
            case 'jpg' : header('content-type: image/jpeg');
            default    : return $response;
        }
    }
}