<?php

use Muyu\Support\Tool;

function db($key = 'default', $setConn = null) : PDO {
    static $conn = [];
    $setConn && $conn[$key] = $setConn;
    empty($conn[$key]) && $conn[$key] = genConn($key);
    return $conn[$key];
}

function genConn($muyuConfig = 'default', $conf = []) {
    return Tool::pdo('database.' . $muyuConfig, $conf);
}

function dbQuery($sql, $para = [], $page = null, $limit = 20) {
    $page !== null && $sql .= ' limit ' . ($page - 1) * $limit . ',' . $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($para);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function dbExec($sql, $para = []) {
    $stmt = db()->prepare($sql);
    return $stmt->execute($para);
}

function dbCount($sql, $para = []) {
    $stmt = db()->prepare($sql);
    $stmt->execute($para);
    $rs = $stmt->fetch();
    return $rs[0] ?? null;
}