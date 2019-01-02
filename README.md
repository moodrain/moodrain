MoodRain
==========

> Chinese version [here](https://github.com/moodrain/moodrain/README_CN.md)

## List：

### Main

* Config Tool
* Curl Package
* Domain Record
* Excel Handler
* FTP Client
* Aliyun OSS

### Secondary

* API Transit
* POP3 Store
* SMTP Client
* Aliyun SMS
* Wechat Public Account

### Informal

* Bwh Migrate
* Downloader
* Lanzou Netdisk

## Install

	composer require moodrain/moodrain

## Config

* call save() of ConfigExample to export json config file
* the defualt path of config file is at the root of project, which you can call Config::setPath() to customize
* some password (moodrain、ftp、pop3、smtp's password) need to be base64 encoded, which will be decoded at the init of these tools


## Start

For fluent interface is implemented, you can get started even without document and only depending on the advice of IDE, like sending a request: 

	$rs = (new Curl('google.com/search'))->query(['q' => 'moodrain github'])->get();

## Contribute

Unit tests have coverd main tools, please run phpunit after configuration