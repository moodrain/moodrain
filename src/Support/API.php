<?php
namespace Muyu\Support;

use Muyu\Config;
use Muyu\Tool;

class API
{
    private $namespace;
    private $module;
    private $action;
    private $params;
    private $config;
    private $subkey;
    private $result;
    private $access;

    function __construct()
    {
        $this->namespace = "Muyu\\";
        $this->subkey = 'default';
        $this->access = [];
    }

    function module(string $module = null)
    {
        if($module)
        {
            $this->module = $module;
            return $this;
        }
        return $this->module;
    }

    function action(string $action = null)
    {
        if($action)
        {
            $this->action = $action;
            return $this;
        }
        return $this->action;
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

    function subkey(string $subkey = null)
    {
        if($subkey)
        {
            $this->subkey = $subkey;
            return $this;
        }
        return $this->subkey;
    }

    function params(array $params = null)
    {
        if($params)
        {
            $this->params = $params;
            return $this;
        }
        return $this->params;
    }

    function access(array $access = null)
    {
        if($access)
        {
            $this->access = $access;
            return $this;
        }
        return $this->access;
    }

    function act()
    {
        $module = $this->namespace . $this->module;
        $module = new $module('', false);
        $module->init($this->config);
        return call_user_func_array([$module, $this->action], $this->params);
    }

    function handle(array $data)
    {
        if(!$this->accessible($data['module'], $data['action']))
            return Tool::res(403, '403 Forbidden', null, 403);
        $config = new Config();
        $this->config($config(strtolower($data['module']) . '.' . ($data['subkey'] ?? 'default')));
        $this->action($data['action']);
        $this->params($data['params']);
        $this->module($data['module']);
        $this->result = $this->act();
        return Tool::res(0, '', $this->result);
    }

    private function accessible($module, $action)
    {
        if(array_key_exists($module, $this->access))
        {
            foreach($this->access[$module] as $accessibleAction)
            {
                if($accessibleAction == $action)
                    return true;
            }
            return false;
        }
        return false;
    }
}