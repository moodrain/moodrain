<?php
namespace Muyu\Support;

class Router
{
    public function route($url = null, $prefix = '') {
        $url = $url ?? $_SERVER['REQUEST_URI'];
        if($prefix && strpos($url, $prefix) === false){
            echo Tool::res(404,'页面不存在', null, 404);
            exit();
        }
        $url = $prefix ? Tool::strReplaceOnce($prefix, '', $url) : $url;
        $request = explode('/', $url);
        foreach($request as $r) {
            if($r === '')
                array_shift($request);
            else
                break;
        }
        echo $this->handle($request);
    }
    private function handle($url) {
        if(count($url) <= 1)
            array_unshift($url, 'Index');
        $controller = ucfirst($url[0]);
        $action = Tool::hump(explode('?', $url[1] ?? '')[0]);
        $action = $action == '' ? 'index' : $action;
        define('Controller', $controller);
        define('Action', $action);
        $class = 'App\Controller\\' . $controller;
        if(!class_exists($class))
            return Tool::res(404,'页面不存在', null, 404);
        $obj = new $class;
        if(!method_exists ($obj, $action))
            return Tool::res(404,'页面不存在', null, 404);
        return call_user_func([$obj, $action]);
    }
}