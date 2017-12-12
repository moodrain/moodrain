<?php
namespace Muyu;
require('vendor/autoload.php');
$config = new Config();
$ups = $config('up');
$curl = (new Curl())->url('https://search.bilibili.com/video');
foreach($ups as $up)
{
    $raw = $curl->query(['keyword' => $up])->get();
    $html = Tool::strBetween($raw, '<ul class="ajax-render" style="width:1100px;">','<div class="footer bili-footer"></div>');
    file_put_contents("$up.html", $html);
}
$curl->close();