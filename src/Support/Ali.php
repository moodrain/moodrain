<?php
namespace Muyu\Support;

class Ali
{
    public static function httpParam($params, $accessKeySecret) {
        $method = 'POST';
        $aliHelper = new Class {
            function urlEncode($str) {
                return str_replace('%7A', '~', str_replace('*', '%2A', str_replace('+' , '%20', urlencode($str))));
            }
        };
        if(date_default_timezone_get() != 'UTC' && isset($params['Timestamp']))
            $params['Timestamp'] = Tool::gmt_iso8601(strtotime($params['Timestamp']), 'UTC');
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