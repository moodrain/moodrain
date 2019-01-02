<?php
use Muyu\Curl;

// 兔玩网的爬虫
$list = [];
$curl = new Curl();
for($i = 1;$i < 1500;$i++) {
    echo $i;
    $rs = $curl->retry(3)->url('https://api.tuwan.com/apps/Welfare/detail?id=' . $i)->post();
    $rs = json_decode(substr($rs, 1, strlen($rs) - 2));
    if($rs === null)
        continue;
    if($rs->error === 0) {
        $list[] = [
            'id' => $i,
            'title' => $rs->title,
            'zip' => $rs->url,
        ];
    }
}
file_put_contents('tw_list.json', json_encode($list, 128|256));