<?php
namespace Muyu\Support;
use Muyu\Support\Traits\MuyuExceptionTrait;

class Seeder
{
    private $seeder;
    private $seederList;

    use MuyuExceptionTrait;
    function __construct($seeder = null) {
        $this->initError();
        $this->initSeederList();
        $this->seeder = $seeder;
    }
    function seeder($seeder = null) {
        if(!$seeder)
            return $this->seeder;
        $this->seeder = $seeder;
        return $this;
    }
    function initSeederList() {
        try {
            $seederList = [];
            $ref = new \ReflectionClass(self::class);
            foreach($ref->getMethods() as $method)
                if($method->isPrivate() && $method->name != 'addError' && $method->name != 'initError')
                    $seederList[] = lcfirst(str_replace('faker', '', $method->name));
            $this->seederList = $seederList;
        } catch (\ReflectionException $e) {return [];}
    }
    function seederList() {
        return $this->seederList;
    }
    function fake()
    {
        if(!$this->seeder)
            return null;
        $method = 'faker' . ucfirst($this->seeder);
        if(!in_array($this->seeder, $this->seederList)) {
            $this->addError(1, 'seeder not exist');
            return false;
        }
        return $this->$method();
    }
    private function fakerName() {
        static $firstName = [
            '王','李','张','刘','陈','杨','黄','赵','吴','周','徐','孙','马','朱','胡','郭','何','高','林','郑','谢','罗','梁','宋','唐','许','韩','冯','邓','曹','彭','曾','肖','田','董','袁','潘','于','蒋','蔡','余','杜','叶','程','苏','魏','吕','丁','任','沈','姚','卢','姜','崔','钟','谭','陆','汪','范','金','石','廖','贾','夏','韦','付','方','白','邹','孟','熊','秦','邱','江','尹','薛','闫','段','雷','侯','龙','史','陶','黎','贺','顾','毛','郝','龚','邵','万','钱','严','覃','武','戴','莫','孔','向','汤',
        ];
        static $secondName = [
            '家乐','志明','展鸿','家意','子荣','子涵','子琴','志鹏','子意','家康','永明','永康','志宁','志成','子轩','志颖','佳颖','佳乐','杰','嘉达','云亮','志伟','文清','文州','文洲','敬言','荣兴','烟柔','柔','建国','果','一帆','一新','心仪','炽诚','文豪','文浩'
        ];
        return Tool::rand($firstName) . Tool::rand($secondName);
    }
    private function fakerMajor() {
        static $major = [
            '计算机科学与技术','药学','药物分析','药物化学','临床药学','预防医学','中医学','中药学','电子信息工程','生物医学工程','生物技术','护理学','英语','健康服务与管理','康复治疗学','生物制药','中药制药',
        ];
        return Tool::rand($major);
    }
    private function fakerSchool() {
        static $schools = [
            '药学院','公共卫生学院','临床医学院','中药学院','医药信息工程学院','生命科学与生物制药学院','护理学院','外国语学院','健康学院'
        ];
        return Tool::rand($schools);
    }
    private function fakerElective() {
        static $elective = [
            '骨骼健康学','名著名片欣赏','生物技术与现代生活','大学生学术毕业论文写作技巧','生物技术的法律法规','奇妙的生物技术','自我控制能力的奥秘','大学生财商教育','Photoshop cs2入门与提高','客家文化','大学语文','影视鉴赏','大学生书法技能','美术鉴赏','舞蹈鉴赏','音乐鉴赏','戏剧鉴赏','文学技巧与欣赏','大学美育'
        ];
        return $elective[array_rand($elective)];
    }
    private function fakerRoom() {
        static $building = ['A','B','C','D','E','F','G','H'];
        $floor = mt_rand(1,7);
        $number = mt_rand(1, 30);
        $number = $number < 10 ? '0' . $number : $number;
        return Tool::rand($building) . '-' . $floor . $number;
    }
    private function fakerIp() {
        $ipType = mt_rand(1, 2);
        if($ipType == 1)
            $ip =  mt_rand(11, 171);
        else if($ipType == 2)
            $ip = mt_rand(193, 254);
        $ip = $ip . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254) . '.' . mt_rand(1, 254);
        return $ip;
    }
}