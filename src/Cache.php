<?php
namespace Muyu;

class Cache {

    static private $dir = './cache';
    static private $infoDir = './cache/info';
    static private $dataDir = './cache/data';
    static private $expire = 86400;

    static public function dir($dir = null) {
        if($dir) {
            self::$dir = $dir;
            self::$infoDir = $dir . '/info';
            self::$dataDir = $dir . '/data';
        }
        return self::$dir;
    }

    static public function expire($expire = null) {
        if($expire) {
            self::$expire = $expire;
        }
        return self::$expire;
    }

    static public function set($key, $data) {
        file_put_contents(self::$infoDir . '/' . $key, json_encode([
            'key' => $key,
            'bin' => false,
            'data' => $data,
            'expire' => time() + self::$expire,
        ], JSON_UNESCAPED_UNICODE));
    }

    static function setBin($key, $data) {
        file_put_contents(self::$infoDir . '/' . $key, json_encode([
            'key' => $key,
            'bin' => true,
            'data' => '',
            'expire' => time() + self::$expire,
        ], JSON_UNESCAPED_UNICODE));
        file_put_contents(self::$dataDir . '/' . $key, $data);
    }

    static function getBin($key) {
        $file = self::$infoDir . '/' . $key;
        if(! file_exists($file)) {
            return null;
        }
        $cache = json_decode(file_get_contents($file));
        if($cache->expire < time()) {
            return null;
        }
        return file_get_contents(self::$dataDir . '/' . $key);
    }

    static function has($key) {
        $file = self::$infoDir . '/' . $key;
        if(! file_exists($file)) {
            return false;
        }
        $cache = json_decode(file_get_contents($file));
        if($cache->expire < time()) {
            return false;
        }
        return true;
    }

    static function get($key) {
        $file = self::$infoDir . '/' . $key;
        if(! file_exists($file)) {
            return null;
        }
        $cache = json_decode(file_get_contents($file));
        if($cache->expire < time()) {
            return null;
        }
        return $cache->bin ? file_get_contents(self::$dataDir . '/' . $key) : $cache->data;
    }

    static function clean() {
        $keys = scandir(self::$infoDir);
        array_shift($keys);
        array_shift($keys);
        $count = 0;
        foreach($keys as $key) {
            $cache = json_decode(file_get_contents(self::$infoDir . '/' . $key));
            if($cache->expire < time()) {
                if($cache->bin) {
                    unlink(self::$dataDir . '/' . $key);
                }
                unlink(self::$infoDir . '/' . $key);
                $count++;
            }
        }
        return $count;
    }

    static function clear() {
        $keys = scandir(self::$infoDir);
        array_shift($keys);
        array_shift($keys);
        $count = 0;
        foreach($keys as $key) {
            $cache = json_decode(file_get_contents(self::$infoDir . '/' . $key));
            if($cache->bin) {
                unlink(self::$dataDir . '/' . $key);
            }
            unlink(self::$infoDir . '/' . $key);
            $count++;
        }
        return $count;
    }

}