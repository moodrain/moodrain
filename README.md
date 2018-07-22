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