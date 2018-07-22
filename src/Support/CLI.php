<?php
namespace Muyu\Support;

use Muyu\Config;

class CLI
{
    private $config;
    private $server;
    private $option;
    private $module;
    private $action;
    private $subkey;

    function __construct()
    {
        $this->server = 'https://moodrain.cn';
        $this->option = getopt('');
        $this->config = new Config();
    }

    function read()
    {

    }

    function config(array $config = null)
    {
        if($config)
        {
            $this->config = $config;
            return $this;
        }
        return $this->config;
    }

    function server(string $server = null)
    {
        if($server)
        {
            $this->server = $server;
            return $this;
        }
        return $this->server;
    }

    function option(array $option = null)
    {
        if($option)
        {
            $this->option = $option;
            return $this;
        }
        return $this->option;
    }

    function subkey(string $subkey = null)
    {
        if($subkey)
        {
            $this->subkey = $subkey;
            return $this;
        }
        return $this->subkey;
    }



}