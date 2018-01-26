沐雨工具库
===========
moodrain.cn
-----------

### 1、内置工具：

* json文件配置
* curl请求解析
* excel文件读写
* 阿里sms发送
* 阿里oss及表单直传
* ftp函数封装
* smtp邮件发送
* pop3及邮件中转
* 微信公众号消息回复
* 常用函数封装

### 2、安装

		composer require moodrain/moodrain
* 需求：PHP 7.0 及以上，php-imap 拓展
* linux 可能没有 php-imap 拓展，请自行安装

### 3、配置

* 该工具库开箱即用，但配置会使编码更加简单高效
* 工具库的很多功能都默认使用了库内的配置工具的设置
* 配置文件格式可以在 ConfigExample 中导出 json 查看
* 一些密码（moodrain网站、ftp、pop3、smtp的密码）均需base64，这些工具初始化会 decode
* 配置文件默认位置在项目根目录，可用 Config::setPath 设置路径

### 4、开始使用

一些工具采用简单、链式接口，几乎不用文档，根据IDE提示即可使用，例如发送请求

		$rs = (new \Muyu\Curl())->url('https://www.baidu.cn/s')->query(['wd' => 'moodrain.cn', 'ie' => 'UTF-8'])->get();
更多工具和使用方法请阅读Wiki


> 以下是旧版README 

MoodRain
================
Only for MoodRain
------------
### Example / Code Process:
		composer require moodrain/moodrain (dev-master)
		cp vendor/moodrain/moodrain/muyu muyu
		php muyu downloadMyuJson demo
		username: muyu@muyu.com
		password: muyu
		
### demo.php
		$config = new Config();
		$ups = $config('demo.crawler.ups');
		$curl = (new Curl())->url('https://search.bilibili.com/video');
		$result = [];
		foreach($ups as $up)
		{
		    $raw = $curl->query(['keyword' => $up])->get();
		    $result[] = $html = Tool::strBetween($raw, '<ul class="ajax-render" style="width:1100px;">','<div class="footer bili-footer"></div>');
		    file_put_contents("$up.html", $html);                                                                           // save in html file
		}
		$curl->close();
		$pdo = Tool::pdo($config('database.demo'));                                                                         // save in database by PDO
		(new OSS())->put("{$ups[0]}.html", "moodrain-demo/crawler.html", "text/html;charset=UTF-8");                        // save in Ali OSS
		(new FTP())->put("{$ups[0]}.html", "crawler.html");                                                                 // save by FTP
		$mailHtml = '<a href="' . $config('oss.address') . '/moodrain-demo/crawler.html">to see the result</a>';
		(new SMTP())->subject('Crawler Complete')->html($mailHtml)->to('muyu@muyu.com')->send();                            // notify by SMTP
		(new SMS())->init($config('sms.demo'))->data(['msg' => 'crawler complete!'])->to('13800138000')->send();            // notify by Ali SMS

### a crawler demo is ready !
### [to read the wiki for more](https://github.com/moodrain/moodrain/wiki)
----------
### Tools are as follows:
* easy and convenient config
* some prepared command
* semantic and simple curl
* useful function library
* mini Ali OSS and SMS
* mini ftp client
* basic xml transformer 
* mini mail sender and receiver
* packaged excel reader and writer

Welcome for your advice.


#### know it:
* xml class is only a copy of overtrue/wechat/src/Kernel/Support/XML.php
* mail sender uses PHPMailer/PHPMailer and reader uses barbushin/php-imap in underlying
* excel reader and writer uses box/spout in underlying
