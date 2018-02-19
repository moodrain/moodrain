<?php
namespace Muyu\Support;

class Ali
{
    public static function httpParam(array $params, string $accessKeySecret) : array
    {
        $method = 'POST';
        $aliHelper = new Class {
            function urlEncode(string $str) : string {
                return str_replace('%7A', '~', str_replace('*', '%2A', str_replace('+' , '%20', urlencode($str))));
            }
        };
        ksort($params, SORT_STRING);
        $strToSign = '';
        foreach($params as $key => $val)
            $strToSign = $strToSign . '&' . $aliHelper->urlEncode($key) . '=' . $aliHelper->urlEncode($val);
        $strToSign = substr($strToSign, 1);
        $strToSign = $method . '&' . $aliHelper->urlEncode('/') . '&' . $aliHelper->urlEncode($strToSign);
        $signature = base64_encode(hash_hmac('sha1', $strToSign, $accessKeySecret . '&', true));
        $params['Signature'] = $signature;
        return $params;
    }
}