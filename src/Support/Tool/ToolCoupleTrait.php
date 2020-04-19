<?php

namespace Muyu\Support\Tool;

use function Muyu\Support\Fun\conf;
use Muyu\Support\HttpStatus;
use Muyu\Support\Router;
use Muyu\Support\Seeder;
use \PDO;

trait ToolCoupleTrait {

    static public function router() {
        return new Router();
    }

    static public function route($prefix = '') {
        $router = self::router();
        $router->route(null, $prefix);
    }

    static public function fake($seeder = null) {
        return (new Seeder($seeder))->seeder($seeder)->fake();
    }

    static public function log($log, $muyuConfig = 'log.default') {
        $file = fopen(conf($muyuConfig . '.file'), 'a');
        $log = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        fwrite($file, $log . PHP_EOL);
        fclose($file);
    }

    static public function logA($log, $level = 'INFO', $muyuConfig = 'log.default') {
        $file = fopen(conf($muyuConfig . '.file'), 'a');
        $log = self::date() . ' ' . $level . ': ' . $log;
        fwrite($file, $log . PHP_EOL);
        fclose($file);
    }

    static public function pdo($muyuConfig = 'database.default', $conf = null, $attr = null) {
        $attr = $attr ?? [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $host = $conf['host'] ?? conf( $muyuConfig . '.host');
        $type = $conf['type'] ?? conf($muyuConfig . '.type');
        $user = $conf['user'] ?? conf($muyuConfig . '.user');
        $pass = base64_decode($conf['pass'] ?? conf($muyuConfig . '.pass'));
        $db   = $conf['db']   ?? conf($muyuConfig . '.db', '');
        return new PDO("$type:host=$host;dbname=$db;charset=utf8", $user, $pass, $attr);
    }

    static public function dbConfigHelper($muyuConfig, $db) {
        $conf = conf($muyuConfig);
        $conf['db'] = $db;
        $conf['pass'] = base64_decode($conf['pass']);
        return $conf;
    }

    static public function res($code = 200, $msg = '', $data = null, $status = null) {
        if($status)
            header(HttpStatus::status($status));
        else
            header(HttpStatus::status($code == 0 ? 200 : $code));
        header('Content-Type: application/json');
        return json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

}