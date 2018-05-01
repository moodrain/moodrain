<?php
namespace Muyu\Support;

class HttpStatus
{
    public static function _200()
    {
        return 'HTTP/1.1 200 OK';
    }
    public static function _301()
    {
        return 'HTTP/1.1 301 Moved Permanently';
    }
    public static function _302()
    {
        return 'HTTP/1.1 302 Found';
    }
    public static function _307()
    {
        return 'HTTP/1.1 307 Temporary Redirect';
    }
    public static function _308()
    {
        return 'HTTP/1.1 308 Permanent Redirect';
    }
    public static function _400()
    {
        return 'HTTP/1.1 400 Bad Request';
    }
    public static function _401()
    {
        return 'HTTP/1.1 401 Unauthorized';
    }
    public static function _403()
    {
        return 'HTTP/1.1 403 Forbidden';
    }
    public static function _404()
    {
        return 'HTTP/1.1 404 Not Found';
    }
    public static function _451()
    {
        return '451 Unavailable For Legal Reasons';
    }
    public static function _500()
    {
        return 'HTTP/1.1 500 Internal Server Error';
    }
}