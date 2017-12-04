<?php
namespace Muyu;

class Curl
{
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