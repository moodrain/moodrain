MoodRain
==========

## 列表：

### 主要

* 配置工具
* Curl 封装
* 域名解析
* Excel 读写
* FTP 客户端

### 次要

* POP3 中转
* SMTP 客户端

### 非正式

* 下载器

## 安装

	composer require moodrain/moodrain

## 配置

* 调用 ConfigExample 的 save 方法导出 json 配置文件
* 配置文件默认路径在项目根目录，调用 Config::setPath 来自定义路径
* 一些密码（moodrain、ftp、pop3、smtp的密码）需要 bsae64，这些工具在初始化时会 decode


## 开始

工具采用了链式接口，甚至不用文档，根据 IDE 的提示即可使用，例如发送请求：

	$rs = (new Curl('google.com/search'))->query(['q' => 'moodrain github'])->get();

## 贡献

单元测试已经覆盖了主要工具，请在配置好后运行 phpunit