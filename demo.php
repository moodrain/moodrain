<?php
namespace Muyu;
require('vendor/autoload.php');
$config = new Config();
$ups = $config('demo.crawler.ups');
$curl = (new Curl())->url('https://search.bilibili.com/video');
$result = [];
foreach($ups as $up)
{
    $raw = $curl->query(['keyword' => $up])->get();
    $result[] = $html = Tool::strBetween($raw, '<ul class="ajax-render" style="width:1100px;">','<div class="footer bili-footer"></div>');
    file_put_contents("$up.html", $html);                                                                         // save in html file
}
$curl->close();
$pdo = Tool::pdo($config('database.demo'));                                                                         // save in database by PDO
(new OSS())->put("{$ups[0]}.html", "moodrain-demo/crawler.html", "text/html;charset=UTF-8");           // save in Ali OSS
$mailHtml = '<a href="' . $config('oss.address') . '/moodrain-demo/crawler.html">to see the result</a>';
(new Mail())->subject('Crawler Complete')->content($mailHtml)->to('muyu@muyu.com')->send();                  // notify by SMTP
(new SMS())->init($config('sms.demo'))->data(['msg' => 'crawler complete!'])->to('13800138000')->send();    // notify by Ali SMS