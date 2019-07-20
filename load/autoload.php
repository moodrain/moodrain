<?php
$d = __DIR__ . '/../src/';

$files = [

    'Support/Traits/MuyuExceptionTrait',

    'Informal/Traits/DownloaderTrait',
    'Informal/Bwh',
    'Informal/Downloader',
    'Informal/Lanzou',

    'Secondary/API',
    'Secondary/POP3',
    'Secondary/POPStore',
    'Secondary/SMS',
    'Secondary/SMTP',
    'Secondary/Wechat',

    'Support/DNS/AliDNS',
    'Support/DNS/CfDNS',
    'Support/DNS/Record',

    'Support/Fun/Helper',
    'Support/Fun/Test',

    'Support/Tool/ToolCoupleTrait',
    'Support/Tool/ToolDecoupleTrait',


    'Support/Ali',
    'Support/ApiUrl',

    'Support/Arr',
    'Support/ConfigExample',
    'Support/DbBuilder',
    'Support/HttpStatus',
    'Support/MuyuException',
    'Support/Router',
    'Support/Seeder',
    'Support/Tool',
    'Support/XML',

    'Cache',
    'Config',
    'Curl',
    'DNS',
    'Excel',
    'FTP',
    'OSS',

];

foreach($files as $file) {
    require_once($d . $file . '.php');
}