MoodRain
================
Only for MoodRain
------------
### Example / Code Process:
		composer require moodrain/moodrain dev-master
		cp vendor/moodrain/moodrain/muyu muyu
		php muyu downloadMyuJson demo
		username: muyu@muyu.com
		password: muyu
		
### demo.php

		namespace Muyu;
		require('vendor/autoload.php');
		$config = new Config();
		$ups = $config('up');
		$curl = (new Curl())->url('https://search.bilibili.com/video');
		foreach($ups as $up)
		{
		    $raw = $curl->query(['keyword' => $up])->get();
		    $html = Tool::strBetween($raw, '<ul class="ajax-render" style="width:1100px;">','<div class="footer bili-footer"></div>');
		    file_put_contents("$up.html", $html);
		}
		$curl->close();

### a crawler demo is ready !
----------
### Tools are as follows:
* easy and convenient config
* some prepared command
* semantic and simple curl
* useful function library
* basic xml transformer 
* mini Ali OSS and SMS (in progress)
* mini mail sender (in progress)

Welcome for your advice.