<?php

namespace Muyu\Support\Traits\Tool;

use Muyu\Support\Seeder;
use \PDO;

trait ToolCoupleTrait {

    static public function fake($seeder = null) {
        return (new Seeder($seeder))->seeder($seeder)->fake();
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

}