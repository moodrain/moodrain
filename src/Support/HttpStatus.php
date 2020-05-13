<?php
namespace Muyu\Support;

class HttpStatus
{
    const map = [
        '200' => 'HTTP/1.1 200 OK',
        '204' => 'HTTP/1.1 204 No Content',
        '301' => 'HTTP/1.1 301 Moved Permanently',
        '302' => 'HTTP/1.1 302 Found',
        '307' => 'HTTP/1.1 307 Temporary Redirect',
        '308' => 'HTTP/1.1 308 Permanent Redirect',
        '400' => 'HTTP/1.1 400 Bad Request',
        '401' => 'HTTP/1.1 401 Unauthorized',
        '403' => 'HTTP/1.1 403 Forbidden',
        '404' => 'HTTP/1.1 404 Not Found',
        '451' => 'HTTP/1.1 451 Unavailable For Legal Reasons',
        '500' => 'HTTP/1.1 500 Internal Server Error',
    ];
    static function status($code) {
        return self::map[(string)$code];
    }
    static function _200() {
        return self::status(200);
    }
    static function _204() {
        return self::status(204);
    }
    static function _301() {
        return self::status(301);
    }
    static function _302() {
        return self::status(302);
    }
    static function _307() {
        return self::status(307);
    }
    static function _308() {
        return self::status(308);
    }
    static function _400() {
        return self::status(400);
    }
    static function _401() {
        return self::status(401);
    }
    static function _403() {
        return self::status(403);
    }
    static function _404() {
        return self::status(404);
    }
    static function _451() {
        return self::status(451);
    }
    static function _500() {
        return self::status(500);
    }
}