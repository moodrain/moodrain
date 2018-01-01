<?php
namespace Muyu;
use \PDO;

class Tool
{
    private static $config;

    public static function pdo(Array $configArr = null, $errMode = PDO::ERRMODE_EXCEPTION)
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
                 $firstName = [ '赵', '钱', '孙', '李', '周', '吴', '郑', '王', '冯', '陈', '楮', '卫', '蒋', '沈', '韩', '杨', '朱', '秦', '尤', '许', '何', '吕', '施', '张', '孔', '曹', '严', '华', '金', '魏', '陶', '姜', '戚', '谢', '邹', '喻', '柏', '水', '窦', '章', '云', '苏', '潘', '葛', '奚', '范', '彭', '郎', '鲁', '韦', '昌', '马', '苗', '凤', '花', '方', '俞', '任', '袁', '柳', '酆', '鲍', '史', '唐', '费', '廉', '岑', '薛', '雷', '贺', '倪', '汤', '滕', '殷', '罗', '毕', '郝', '邬', '安', '常', '乐', '于', '时', '傅', '皮', '卞', '齐', '康', '伍', '余', '元', '卜', '顾', '孟', '平', '黄', '和', '穆', '萧', '尹', '姚', '邵', '湛', '汪', '祁', '毛', '禹', '狄', '米', '贝', '明', '臧', '计', '伏', '成', '戴', '谈', '宋', '茅', '庞', '熊', '纪', '舒', '屈', '项', '祝', '董', '梁', '杜', '阮', '蓝', '闽', '席', '季', '麻', '强', '贾', '路', '娄', '危', '江', '童', '颜', '郭', '梅', '盛', '林', '刁', '锺', '徐', '丘', '骆', '高', '夏', '蔡', '田', '樊', '胡', '凌', '霍', '虞', '万', '支', '柯', '昝', '管', '卢', '莫', '经', '房', '裘', '缪', '干', '解', '应', '宗', '丁', '宣', '贲', '邓', '郁', '单', '杭', '洪', '包', '诸', '左', '石', '崔', '吉', '钮', '龚', '程', '嵇', '邢', '滑', '裴', '陆', '荣', '翁', '荀', '羊', '於', '惠', '甄', '麹', '家', '封', '芮', '羿', '储', '靳', '汲', '邴', '糜', '松', '井', '段', '富', '巫', '乌', '焦', '巴', '弓', '牧', '隗', '山', '谷', '车', '侯', '宓', '蓬', '全', '郗', '班', '仰', '秋', '仲', '伊', '宫', '宁', '仇', '栾', '暴', '甘', '斜', '厉', '戎', '祖', '武', '符', '刘', '景', '詹', '束', '龙', '叶', '幸', '司', '韶', '郜', '黎', '蓟', '薄', '印', '宿', '白', '怀', '蒲', '邰', '从', '鄂', '索', '咸', '籍', '赖', '卓', '蔺', '屠', '蒙', '池', '乔', '阴', '郁', '胥', '能', '苍', '双', '闻', '莘', '党', '翟', '谭', '贡', '劳', '逄', '姬', '申', '扶', '堵', '冉', '宰', '郦', '雍', '郤', '璩', '桑', '桂', '濮', '牛', '寿', '通', '边', '扈', '燕', '冀', '郏', '浦', '尚', '农', '温', '别', '庄', '晏', '柴', '瞿', '阎', '充', '慕', '连', '茹', '习', '宦', '艾', '鱼', '容', '向', '古', '易', '慎', '戈', '廖', '庾', '终', '暨', '居', '衡', '步', '都', '耿', '满', '弘', '匡', '国', '文', '寇', '广', '禄', '阙', '东', '欧', '殳', '沃', '利', '蔚', '越', '夔', '隆', '师', '巩', '厍', '聂', '晁', '勾', '敖', '融', '冷', '訾', '辛', '阚', '那', '简', '饶', '空', '曾', '毋', '沙', '乜', '养', '鞠', '须', '丰', '巢', '关', '蒯', '相', '查', '后', '荆', '红', '游', '竺', '权', '逑', '盖', '益', '桓', '公', '仉', '督', '晋', '楚', '阎', '法', '汝', '鄢', '涂', '钦', '岳', '帅', '缑', '亢', '况', '后', '有', '琴', '归', '海', '墨', '哈', '谯', '笪', '年', '爱', '阳', '佟', '商', '牟', '佘', '佴', '伯', '赏', '公孙', '欧阳','太史','端木','上官','司马','东方','独孤','南宫','万俟','闻人','夏侯','诸葛','尉迟','公羊','赫连','澹台','皇甫','宗政','濮阳','公冶','太叔','申屠','公孙','慕容','仲孙','钟离','长孙','宇文','司徒','鲜于','司空','闾丘','子车','亓官','司寇','巫马','公西','颛孙','壤驷','公良','漆雕','乐正','宰父','谷梁','拓跋','夹谷','轩辕','令狐','段干','百里','呼延','东郭','南门','羊舌','微生','公户','公玉','公仪','梁丘','公仲','公上','公门','公山','公坚','左丘','公伯','西门','公祖','第五','公乘','贯丘','公皙','南荣','东里','东宫','仲长','子书','子桑','即墨','达奚','褚师','吴铭'];

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
        }
    }
    public static function isSet($key, Array $array)
    {
        return array_key_exists($key, $array);
    }
    public static function ext($filename)
    {
        return explode('.', $filename)[1] ?? null;
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