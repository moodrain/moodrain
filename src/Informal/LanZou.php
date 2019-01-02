<?php
namespace Muyu\Informal;

use Muyu\Curl;
use function Muyu\Support\Fun\conf;
use Muyu\Support\Tool;

// 非正式类，没有添加异常处理， 没有收集apiurl
class LanZou {

    private $user;
    private $pass;
    private $cookie = [];

    function __construct($muyuConfig = 'lanzou.default', $init = true) {
        if($init)
            $this->init(conf($muyuConfig));
    }
    function init(array $config) {
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->login();
    }
    function list($folder = '') {
        $files = [];
        if(!$folder)
            $curl = new Curl('https://up.woozooo.com/mydisk.php?item=files&action=index&u=' . $this->user);
        else {
            $deep = 1;
//            暂时只支持一级目录，请思考无限级目录的做法
//            $deeps = explode('/', $folder);
//            foreach($deeps as $d)
//                if($d)
//                    $deep++;
            $folderId = '';
            $folders = $this->list();
            foreach($folders as $f)
                if($f['name'] == $folder)
                    $folderId = $f['id'];
            $curl = new Curl('https://up.woozooo.com/mydisk.php?item=files&action=index&folder_node=' . $deep . '&folder_id=' . $folderId);
        }
        $html = $curl->cookie($this->cookie)->get();
        $dirs = Tool::strBetween($html, '<div id="sub_folder_list">','<div id="infomorenew">');
        $dirs = explode('<div class="f_tb"', $dirs);
        array_shift($dirs);
        $dirInfo = [];
        foreach($dirs as $dir) {
            $aDir = [];
            $aDir['id'] = Tool::strBetween($dir, 'folder_id=', '"><img');
            $aDir['name'] = Tool::strBetween($dir, 'align="absmiddle" />&nbsp;', '</a>');
            $aDir['dir'] = 1;
            $aDir['ext'] = '';
            $aDir['size'] = '';
            $dirInfo[] = $aDir;
        }
        return array_merge($files, $dirInfo);
    }
    private function login() {
        $curl4Hash = new Curl('https://pc.woozooo.com/mydisk.php');
        $hash = Tool::strBetween($curl4Hash->get(), 'formhash" value="', '" />');
        $curl4Login = new Curl('https://pc.woozooo.com/account.php');
        $this->cookie = $curl4Login->data([
            'action' => 'login',
            'task' => 'login',
            'ref' => '/mydisk.php',
            'formhash' => $hash,
            'username' => $this->user,
            'password' => $this->pass,
        ])->post(false)->cookie();
        return $this->cookie == null;
    }
}