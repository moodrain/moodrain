<?php
namespace Muyu;
require('vendor/autoload.php');
$giftPdo = Tool::pdo('db_yy_gift');
$date = '2017-11-08 00:00:00';
$days = 1;
$roomId = 2147790;
$sql = '';
for($i = 0;$i < $days;$i++)
{
    for($j = 0;$j < 24;$j++)
    {
        $beginTime = date('Y-m-d H:i:s', strtotime("+$i days +$j hour", strtotime($date)));
        $endTime = date('Y-m-d H:i:s', strtotime('+1 hour', strtotime($beginTime)));
        if(!($i == 0 && $j == 0))
            $sql .= ' union all ';
        $sql .= "select sum(number * value) / 10 as money from tb_gift_record_new where roomid = $roomId and createtime >= '$beginTime' and createtime < '$endTime'";
    }
}
$stmt = $giftPdo->prepare($sql);
$stmt->execute();
$rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
$roomPdo = Tool::pdo('db_yy_room');
$roomNameStmt = $roomPdo->prepare('select title from tb_room_info where roomid = ?');
$roomNameStmt->execute([$roomId]);
$roomName = $roomNameStmt->fetch(\PDO::FETCH_OBJ)->title;
$response = [];
$showBeginTime = strtotime($date);
foreach($rs as $r)
{
    $hour = [];
    $hour['roomId'] = $roomId;
    $hour['roomName'] = $roomName;
    $hour['money'] = $r['money'] ?? 0;
    $hour['time'] = date('Y-m-d') . '，' . date('H') . '点-' . (date('H') + 1) . '点';
    $response[] = $hour;
    $showBeginTime = strtotime('+1 hour', $showBeginTime);
}