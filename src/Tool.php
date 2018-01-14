<?php
namespace Muyu;
use \PDO;

class Tool
{
    private static $config;

    public static function validate($type, $value)
    {
        switch($type)
        {
            case 'phone' : return preg_match("/^1[34578]\d{9}$/", $value);
            case 'email' : return filter_var($value, FILTER_VALIDATE_EMAIL);
            case 'url'   : return filter_var($value, FILTER_VALIDATE_URL);
            case 'ip'    : return filter_var($value, FILTER_VALIDATE_IP);
            case 'int'   : return filter_var($value, FILTER_VALIDATE_INT);
            case 'float' : return filter_var($value, FILTER_VALIDATE_FLOAT);
        }
    }
    public static function timezone(string $timezone = 'PRC')
    {
        date_default_timezone_set($timezone);
    }
    public static function date()
    {
        return date('Y-m-d H:i:s');
    }
    public static function rand(array $array)
    {
        return $array[array_rand($array)];
    }
    public static function hump(string $str)
    {
        return preg_replace_callback('/([-_]+([a-z]{1}))/i',function($matches){
            return strtoupper($matches[2]);
        }, $str);
    }
    public static function pdo(array $configArr = null, $errMode = PDO::ERRMODE_EXCEPTION)
    {
        if(!self::$config)
            self::$config = new Config();
        $config  = self::$config;
        $host = $configArr['host'] ?? $config('database.host');
        $type = $configArr['type'] ?? $config('database.type');
        $db = $configArr['db'] ?? $config('database.db');
        $user = $configArr['user'] ?? $config('database.user');
        $pass = $configArr['pass'] ?? $config('database.pass');
        return new PDO("$type:host=$host;dbname=$db;charset=utf8", $user, $pass, [PDO::ATTR_ERRMODE => $errMode]);
    }
    public static function log($log)
    {
        $file = fopen('log.txt', 'a');
        if(!is_string($log))
            $log = json_encode($log, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        fwrite($file, $log . PHP_EOL);
    }
    public static function res($code, $msg, $data, $status = null)
    {
        $statusHeader = 'HTTP/1.1 ';
        switch($status)
        {
            case 200 : $statusHeader .= '200 OK';break;
            case 301 : $statusHeader .= '301 Moved Permanently';break;
            case 302 : $statusHeader .= '302 Found';break;
            case 307 : $statusHeader .= '307 Temporary Redirect';break;
            case 308 : $statusHeader .= '308 Permanent Redirect';break;
            case 400 : $statusHeader .= '400 Bad Request';break;
            case 401 : $statusHeader .= '401 Unauthorized';break;
            case 403 : $statusHeader .= '403 Forbidden';break;
            case 404 : $statusHeader .= '404 Not Found';break;
            case 451 : $statusHeader .= '451 Unavailable For Legal Reasons';break;
            case 500 : $statusHeader .= '500 Internal Server Error';break;
            default  : $statusHeader .= '200 OK';
        }
        header($statusHeader);
        header('Content-Type: application/json');
        return json_encode(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }
    public static function abc123($in, $up = false)
    {
        $ascii = ord($in);
        switch($ascii)
        {
            case ($ascii >= 65 && $ascii <= 90) : return $ascii - 64;
            case ($ascii >= 97 && $ascii <= 122) : return $ascii - 96;
            case ($in >= 1 && $in <= 26 && $up) : return chr($in + 64);
            case ($in >= 1 && $in <= 26 && !$up) : return chr($in + 96);
            default : return null;
        }
    }
    public static function deep($arr)
    {
        $deep = 1;
        if(!is_array($arr))
            return 0;
        while(is_array(current($arr)))
        {
            $deep++;
            $arr = current($arr);
        }
        return $deep;
    }
    public static function strBetween($str, $kw1, $kw2)
    {
        $st = stripos($str, $kw1);
        $ed = stripos($str, $kw2);
        if(!$st || !$ed || $ed <= $st)
            return '';
        $str = substr($str, $st + strlen($kw1), $ed - $st - strlen($kw1));
        return $str;
    }
    public static function seed($seeder)
    {
        switch($seeder)
        {
            case 'name' :
            {
                $firstName = ['王','李','张','刘','陈','杨','黄','赵','吴','周','徐','孙','马','朱','胡','郭','何','高','林','郑','谢','罗','梁','宋','唐','许','韩','冯','邓','曹','彭','曾','肖','田','董','袁','潘','于','蒋','蔡','余','杜','叶','程','苏','魏','吕','丁','任','沈','姚','卢','姜','崔','钟','谭','陆','汪','范','金','石','廖','贾','夏','韦','付','方','白','邹','孟','熊','秦','邱','江','尹','薛','闫','段','雷','侯','龙','史','陶','黎','贺','顾','毛','郝','龚','邵','万','钱','严','覃','武','戴','莫','孔','向','汤'];
                 $secondName = ['家乐','志明','展鸿','家意','子荣','子涵','子琴','志鹏','子意','家康','永明','永康','志宁','志成','子轩','志颖','佳颖','佳乐','杰','嘉达','云亮','志伟','文清','文州','文洲','敬言','荣兴','烟柔','柔','建国','果','一帆','一新','心仪','炽诚','文豪','文浩'];
                return $firstName[array_rand($firstName)] . $secondName[array_rand($secondName)];
            }
            case 'major' :
            {
                 $majors = [
                    '计算机科学与技术','药学','药物分析','药物化学','临床药学','预防医学','中医学','中药学','电子信息工程','生物医学工程','生物技术','护理学','英语','健康服务与管理','康复治疗学','生物制药','中药制药',
                ];
                return $majors[array_rand($majors)];
            }
            case 'school' :
            {
                 $schools = [
                    '药学院','公共卫生学院','临床医学院','中药学院','医药信息工程学院','生命科学与生物制药学院','护理学院','外国语学院','健康学院'
                ];
                return $schools[array_rand($schools)];
            }
            case 'elect' :
            {
                $elects = [
                    '骨骼健康学','名著名片欣赏','生物技术与现代生活','大学生学术毕业论文写作技巧','生物技术的法律法规','奇妙的生物技术','自我控制能力的奥秘','大学生财商教育','Photoshop cs2入门与提高','客家文化','大学语文','影视鉴赏','大学生书法技能','美术鉴赏','舞蹈鉴赏','音乐鉴赏','戏剧鉴赏','文学技巧与欣赏','大学美育'
                ];
                return $elects[array_rand($elects)];
            }
            case 'room' :
            {
                $buildings = ['A','B','C','D','E','F','G','H'];
                $floor = mt_rand(1,7);
                $number = mt_rand(1, 30);
                $number = $number < 10 ? '0' . $number : $number;
                return $buildings[array_rand($buildings)] . '-' . $floor . $number;
            }
            case 'ip' :
            {
                $ipType = mt_rand(1, 2);
                if($ipType == 1)
                    $ip =  mt_rand(11, 171);
                else if($ipType == 2)
                    $ip = mt_rand(193, 254);
                $ip = $ip . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254);
                return $ip;
            }
        }
    }
    public static function isSet($key, array $array)
    {
        return array_key_exists($key, $array);
    }
    public static function ext($filename)
    {
        return explode('.', basename($filename))[1] ?? null;
    }
    public static function gmt()
    {
        return gmdate('D, d M Y H:i:s T');
    }
    public static function gmt_iso8601($time)
    {
        $dtStr = date("c", $time);
        $myDatetime = new \DateTime($dtStr);
        $expiration = $myDatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration."Z";
    }
}