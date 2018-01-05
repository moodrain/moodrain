<?php
namespace Muyu;

/**
 * Config class used for enabling simple usage of setting with muyu.json.
 *
 * $config is the only one static property, so it is sync in whole app.
 * muyu.json should be placed in root dir so that this class load it when constructing.
 * or you can call init($config) to load your config which should be array.
 * all the keys is divided by '.'
 *
 * the summarizes of methods:
 *
 * init(array $config)
 *      Manually load your config.
 *
 * firstInit(array $config)
 *      Create a new muyu.json and put down $config in JSON, then exit.
 *
 * tryInit(array $config, bool $write = false)
 *      Try to load muyu.json, load $config in para instead if muyu.json doesn't existed.
 *
 * set($key, $val)
 *      Set a new key.
 *
 * reset($key, $val)
 *      Set a key, override existed keys.
 *
 * get($key)
 *      Get an existed key.
 *
 * try($key, $default)
 *      Try to get a key, return $default if the key doesn't existed.
 *
 * dump()
 *      var_dump() all config
 *
 * __invoke($key)
 *      The same as method get.
 *
 * __invoke($key, $default)
 *      The same as method try.
 *
 */


class Config
{
    private static $config;
    public function __construct(array $config = null)
    {
        if(!self::$config)
        {
            if($config !== null)
                $this->init($config);
            else
            {
                if(file_exists('muyu.json'))
                    $this->init(json_decode(file_get_contents('muyu.json'), true));
                else
                    $this->init([]);
            }
        }
    }
    public function init(array $config = null)
    {
        if($config === null)
            throw new \Exception('invalid config format');
        self::$config = $config;
    }
    public function firstInit(array $config)
    {
        if(file_exists('muyu.json'))
            throw new \Exception('muyu.json already exists');
        else
        {
            file_put_contents('muyu.json', json_encode($config, JSON_PRETTY_PRINT));
            echo 'muyu.json has created, build an amazing webapp!';
            exit();
        }
    }
    public function tryInit(array $config, bool $write = false)
    {
        if(file_exists('muyu.json'))
            $this->init(json_decode(file_get_contents('muyu.json'), true));
        else
        {
            $this->init($config);
            if($write)
                file_put_contents('muyu.json', json_encode($config, JSON_PRETTY_PRINT));
        }
    }
    public function set(string $key, $val)
    {
        $raw = $key;
        $config = &self::$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key)
        {
            if($count + 1 == $depth)
            {
                if(!isset($config[$key]))
                    $config[$key] = $val;
                else
                    throw new \Exception('config already set : ' .$raw);
            }
            else
            {
                if(!isset($config[$key]))
                    $config[$key] = [];
                else if(!is_array($config[$key]))
                    throw new \Exception('config format error : ' . $raw);
                $config = &$config[$key];
            }
        }
    }
    public function reset(string $key, $val)
    {
        $raw = $key;
        $config = &self::$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key)
        {
            if($count + 1 == $depth)
                $config[$key] = $val;
            else
            {
                if(!isset($config[$key]) || !is_array($config[$key]))
                    $config[$key] = [];
                $config = &$config[$key];
            }
        }
    }
    public function get(string $key)
    {
        $raw = $key;
        $config = &self::$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key)
        {
            if($count + 1 == $depth && isset($config[$key]))
                return $config[$key];
            else
            {
                if(isset($config[$key]))
                    $config = &$config[$key];
                else
                    throw new \Exception('config not found : ' . $raw);
            }
        }
    }
    public function try(string $key, $default)
    {
        $raw = $key;
        $config = &self::$config;
        $keys = explode('.', $key);
        $depth = count($keys);
        foreach($keys as $count => $key)
        {
            if($count + 1 == $depth && isset($config[$key]))
                return $config[$key];
            else
            {
                if(isset($config[$key]))
                    $config = &$config[$key];
                else
                    return $default;
            }
        }
    }
    public function __invoke(...$paras)
    {
        if(Tool::isSet(1,$paras))
            return $this->try($paras[0], $paras[1]);
        else
            return $this->get($paras[0]);
    }
    public function dump()
    {
        var_dump(self::$config);
    }
}