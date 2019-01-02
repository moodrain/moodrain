<?php
namespace Muyu;

use Muyu\Support\Tool;
use Muyu\Support\Traits\MuyuExceptionTrait;

class Config
{
    private static $config;
    private static $path;

    use MuyuExceptionTrait;
    function __construct($config = null) {
        $this->initError();
        if($config !== null)
            $this->init($config);
        else if (self::$path) {
            if (!file_exists(self::$path))
               throw $this->addError(1, 'config file not found');
            $rs = json_decode(file_get_contents(self::$path), true);
            if (!$rs)
                throw $this->addError(2, 'parse json error');
            $this->init($rs);
        } else
            $this->init([]);
    }
    static function setPath($path) {
        self::$path = $path;
    }
    static function getPath() {
        return self::$path;
    }
    function init($config = null) {
        if($config === null)
            throw $this->addError(3, 'invalid config format');
        self::$config = $config;
        return $this;
    }
    function firstInit($config) {
        if(file_exists(self::$path))
            throw $this->addError(4, 'config file already exists');
        file_put_contents(self::$path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    function set($key, $val) {
        $raw = $key;
        $config = &self::$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key) {
            if($count + 1 == $depth) {
                if(!isset($config[$key]))
                    $config[$key] = $val;
                else
                    throw $this->addError(5, 'config already set : ' .$raw);
            }
            else {
                if(!isset($config[$key]))
                    $config[$key] = [];
                else if(!is_array($config[$key]))
                    throw $this->addError(6, 'config format error : ' . $raw);
                $config = &$config[$key];
            }
        }
    }
    function reset($key, $val) {
        $config = &self::$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key) {
            if($count + 1 == $depth)
                $config[$key] = $val;
            else {
                if(!isset($config[$key]) || !is_array($config[$key]))
                    $config[$key] = [];
                $config = &$config[$key];
            }
        }
    }
    function get($key) {
        if($key === '')
            return null;
        $raw = $key;
        $config = &self::$config;

        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key) {
            if($count + 1 == $depth && isset($config[$key]))
                return $config[$key];
            else {
                if(isset($config[$key]))
                    $config = &$config[$key];
                else
                    throw $this->addError(7, 'config not found : ' . $raw);
            }
        }
    }
    function try($key, $default = null) {
        $config = &self::$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key) {
            if($count + 1 == $depth && isset($config[$key]))
                return $config[$key];
            else {
                if(isset($config[$key]))
                    $config = &$config[$key];
                else
                    return $default;
            }
        }
    }
    function modify($key, $val) {
        $config = json_decode(file_get_contents(self::$path), true);
        if(!$config)
            throw $this->addError(2, 'parse json error');
        $data = &$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key) {
            if($count + 1 == $depth)
                $config[$key] = $val;
            else {
                if(!isset($config[$key]) || !is_array($config[$key]))
                    $config[$key] = [];
                $config = &$config[$key];
            }
        }
        file_put_contents(self::$path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    function __invoke(...$paras) {
        return Tool::isSet(1, $paras) ? $this->try($paras[0], $paras[1]) : $this->get($paras[0]);
    }
    function dump() {
        var_dump(self::$config);
    }
}