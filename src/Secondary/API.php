<?php
namespace Muyu\Secondary;

use Muyu\Support\Tool;
use Muyu\Support\Traits\MuyuExceptionTrait;

/*
$api = new API();
$api->access(['OSS' => ['list']]);
$rs = $api->handle([
    'module' => 'OSS',
    'action' => 'list',
    'params' => ['else'],
]);
var_dump($rs);
*/

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

    use MuyuExceptionTrait;
    function __construct() {
        $this->initError();
        $this->namespace = "Muyu\\";
        $this->subkey = 'default';
        $this->access = [];
        $this->format = 'json';
    }
    function module($module = null) {
        if(!$module)
            return $this->module;
        $this->module = $module;
        return $this;
    }
    function action($action = null) {
        if(!$action)
            return $this->action;
        $this->action = $action;
        return $this;
    }
    function config($config = null) {
        if(!$config)
            return $this->config;
        $this->config = $config;
        return $this;
    }
    function subkey($subkey = null) {
        if(!$subkey)
            return $this->subkey;
        $this->subkey = $subkey;
        return $this;
    }
    function params($params = null) {
        if(!$params)
            return $this->params;
        $this->params = $params;
        return $this;
    }
    function access($access = null) {
        if(!$access)
            return $this->access;
        $this->access = $access;
        return $this;
    }
    function act() {
        $module = $this->namespace . $this->module;
        if($this->config) {
            $module = new $module('', false);
            $module->init($this->config);
        }
        else if($this->subkey)
            $module = new $module(strtolower($this->module) . '.' . $this->subkey);
        else {
            $this->addError(1, 'config not set');
            return false;
        }
        return call_user_func_array([$module, $this->action], $this->params);
    }
    function handle($data) {
        if(!isset($data['module']) || !isset($data['action'])) {
            $this->addError(2, 'module or action not set');
            return Tool::res(400, 'module or action not set', $this->error, 400);
        }
        if(!$this->accessible($data['module'], $data['action'])) {
            $this->addError(3, '403 Forbidden');
            return Tool::res(403, '403 Forbidden', $this->error, 403);
        }
        $this->module($data['module']);
        $this->action($data['action']);
        $this->config($data['config'] ?? []);
        $this->subkey($data['subkey'] ?? '');
        $this->params($data['params'] ?? []);
        $this->result = $this->act();
        return $this->error->ok() ? Tool::res(0, '', $this->result) : Tool::res(500, 'handle error', $this->error, 500);
    }
    private function accessible($module, $action) {
        if(array_key_exists($module, $this->access)) {
            foreach($this->access[$module] as $accessibleAction)
                if($accessibleAction == $action)
                    return true;
            return false;
        }
        return false;
    }
}