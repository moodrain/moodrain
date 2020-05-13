<?php
namespace Muyu;

use Muyu\Support\Tool;
use Muyu\Support\Traits\MuyuExceptionTrait;
use function Muyu\Support\Fun\conf;

class FTP
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $ssl;
    private $pasv;
    private $prefix;
    private $conn;
    private $force;
    private $local;
    private $server;

    use MuyuExceptionTrait;

    function __construct($muyuConfig = 'ftp.default', $init = true) {
        $this->initError();
        if($init) {
            $this->init(conf($muyuConfig));
        }
    }

    function init($config  = []) {
        foreach($config as $key => $val)  {
            $this->$key = $val;
        }
        $this->pass = base64_decode($config['pass'] ?? '');
        $this->force = false;
        $this->conn = $this->ssl ? @ftp_ssl_connect($this->host, $this->port) : @ftp_connect($this->host, $this->port);
        if(! $this->conn) {
            $this->addErr(self::el['connectFail']);
            return false;
        }
        if(! @ftp_login($this->conn, $this->user, $this->pass)) {
            $this->addErr(self::el['loginFail']);
            return false;
        }
        if($this->pasv) {
            @ftp_pasv($this->conn, true);
        }
        if($this->prefix && ! $this->prefix($this->prefix)) {
            return false;
        }
        return $this->conn && $this->error->ok() ? $this : false;
    }

    function prefix($prefix = null) {
        if($prefix) {
            $type = $this->type($prefix, false);
            if(! $type && ! $this->mkdir($this->prefix, false)) {
                return false;
            }
            else if($type == 'file') {
                $this->addErr(self::el['prefixIsFile'], $this->prefix);
            }
            if($this->error->ok()) {
                $this->prefix = $prefix;
            }
            else {
                return false;
            }
            return $this;
        }
        return $this->prefix;
    }

    function force($force = null) {
        if($force === null) {
            return $this->force;
        }
        else {
            $this->force = $force;
            return $this;
        }
    }

    function enforce() {
        $this->force = true;
        return $this;
    }

    function safe() {
        $this->force = false;
        return $this;
    }

    function local($local = null) {
        if(! $local) {
            return $this->local;
        }
        $this->local = $local;
        return $this;
    }

    function server($server = null) {
        if(! $server) {
            return $this->server;
        }
        $this->server = $server;
        return $this;
    }

    function type($file = null, $withPrefix = true) {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        if(!$file) {
            $this->addErr(self::el['targetNotSet']);
            return false;
        }
        if(! $ll = $this->ll(dirname($file), false)) {
            return false;
        }
        $exist = false;
        $type  = null;
        foreach($ll as $l) {
            if($l[0] == basename($file)) {
                $exist = true;
                $type = $l[1];
                break;
            }
        }
        return $exist ? $type : null;
    }

    function list($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? ($this->prefix . $dir) : $dir;
        $rs = @ftp_nlist($this->conn, $dir);
        if(! $rs) {
            $this->addErr(self::el['listFail'], $dir);
            return false;
        }
        return $rs;
    }

    function ll($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? ($this->prefix . $dir) : $dir;
        $ll = @ftp_rawlist($this->conn, $dir);
        if($ll === false) {
            $this->addErr(self::el['llFail'], $dir);
            return false;
        }
        $files = [];
        foreach($ll as $l) {
            $l = explode(' ', $l);
            $name = $l[count($l)-1];
            $type = $l[0]{0};
            switch($type) {
                case '-': $type = 'file';break;
                case 'd': $type = 'directory';break;
                case 'l': $type = 'link';break;
                case 'b': $type = 'block device'; break;
                case 'c': $type = 'character device';break;
                default:  $type = 'unknown type';
            }
            $files[] = [$name, $type];
        }
        return $files;
    }

    function download(string $file = null, $local = null, $withPrefix = true) {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        $local = $local ?? $this->local ?? basename($file);
        if(! $file || ! $local) {
            $this->addErr(self::el['fileNotSet'], $local, $file);
            return false;
        }
        $type = $this->type($file, false);
        if(! $type) {
            $this->addErr(self::el['noServerFile'], $file);
        }
        else if($type == 'directory') {
            $this->addErr(self::el['targetIsDir'], $file);
        }
        else if(is_resource($local)) {
            return @ftp_fget($this->conn, $local, $file, FTP_BINARY);
        }
        else if(file_exists($local) && !$this->force) {
            $this->addErr(self::el['localExists'], $local);
        }
        else if(!file_exists(dirname($local))) {
            Tool::mkdir(dirname($local));
        }
        if($this->error->ok() && ! @ftp_get($this->conn, $local, $file, FTP_BINARY)) {
            $this->addErr(self::el['downloadFail'], $file);
        }
        return $this->error->ok();
    }

    function get(string $file = null, $withPrefix = true) {
        $tmp = tempnam('', '');
        $rs = $this->enforce()->download($file, $tmp, $withPrefix);
        if(! $rs) {
            unlink($tmp);
            return false;
        }
        return file_get_contents($tmp);
    }

    function put($local = null, $file = null, $withPrefix = true) {
        $local = $local ?? $this->local;
        $file = $file ?? $this->server ?? basename($local);
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        if(! $file || ! $local) {
            $this->addErr(self::el['fileNotSet'], $local, $file);
            return false;
        }
        $isResource = is_resource($local);
        if(! $isResource && ! file_exists($local)) {
            $this->addErr(self::el['noLocalFile'], $local);
            return false;
        }
        $type = $this->type($file, false);
        if($type && ! $this->force) {
            $this->addErr(self::el['serverExists'], $file);
            return false;
        }
        if($type == 'directory') {
            $this->addErr(self::el['dupNameDir'], $file);
            return false;
        }
        $dir = dirname($file);
        $dirType = $this->type($dir, false);
        if(! $dirType && ! $this->force) {
            $this->addErr(self::el['noServerDir'], $dir);
        }
        if(! $this->mkdir($dir)) {
            $this->addErr(self::el['mkdirFail'], $dir);
            return false;
        }
        if($this->error->ok() && ! ($isResource ? @ftp_fput($this->conn, $file, $local, FTP_BINARY) : @ftp_put($this->conn, $file, $local, FTP_BINARY))) {
            $this->addErr(self::el['uploadFail'], $file);
        }
        return $this->error->ok();
    }

    function mkdir($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$dir) {
            $this->addErr(self::el['targetNotSet'], $dir);
            return false;
        }
        $dir = trim($dir, '/');
        $dirArr = explode('/', $dir);
        $realDir = '';
        foreach($dirArr as $dirItem) {
            $realDir =  $realDir ? ($realDir . '/' . $dirItem) : $dirItem;
            $type = $this->type($realDir, false);
            if($type == 'directory') {
                continue;
            } else if($type == 'file') {
                $this->addErr(self::el['dupNameFile'], $realDir);
            } else {
                if($this->error->ok() && ! @ftp_mkdir($this->conn, $realDir)) {
                    $this->addErr(self::el['mkdirFail'], $realDir);
                }
            }
        }
        return true;
    }

    function rmdir($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$dir) {
            $this->addErr(self::el['targetNotSet'], $dir);
            return false;
        }
        $type  = $this->type($dir, false);
        if(!$type && !$this->force) {
            $this->addErr(self::el['noServerDir'], $dir);
            return false;
        }
        $ll = $this->ll($dir, false);
        if(count($ll) && !$this->force) {
            $this->addErr(self::el['serverDirFill'], $dir);
            return false;
        }
        foreach($ll as $l) {
            $name = $dir . '/' . $l[0];
            $type = $l[1];
            if($type == 'file' && $this->error->ok() && !@ftp_delete($this->conn, $name)) {
                $this->addErr(self::el['rmFileFail'], $name);
            }
            else if($type == 'directory') {
                $this->rmdir($name, false);
            }
        }
        if($this->error->ok() && ! @ftp_rmdir($this->conn, $dir)) {
            $this->addErr(self::el['rmDirFail'], $dir);
        }
        return $this->error->ok();
    }

    function del($file = null, $withPrefix = true) {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        if(! $file) {
            $this->addErr(self::el['targetNotSet'], $file);
            return false;
        }
        $type = $this->type($file, false);
        if(! $type && ! $this->force) {
            $this->addErr(self::el['noServerFile'], $file);
        }
        else if($type == 'directory') {
            $this->addErr(self::el['targetIsDir'], $file);
        }
        else if($type == 'file' && ! @ftp_delete($this->conn, $file)) {
            $this->addErr(self::el['rmFileFail'], $file);
        }
        return $this->error->ok();
    }

    function close() {
        if(is_resource($this->conn)) {
            @ftp_close($this->conn);
        }
    }

    function __destruct() {
        $this->close();
    }

    const el = [
        'targetNotSet'  => 'target not set',
        'fileNotSet'    => 'local: ? server: ?',
        'llFail'        => 'll fail: ?',
        'listFail'      => 'list fail: ?',
        'noServerFile'  => 'server file not found: ?',
        'noLocalFile'   => 'local file not found: ?',
        'noServerDir'   => 'server dir not found: ?',
        'targetIsDir'   => 'expect target is file, get dir: ?',
        'localExists'   => 'local file already exists: ?',
        'serverExists'  => 'server file already exists: ?',
        'downloadFail'  => 'download file fail: ?',
        'dupNameFile'   => 'expect name is dir, get file: ?',
        'dupNameDir'    => 'expect name is file, get dir: ?',
        'uploadFail'    => 'upload file fail: ?',
        'mkdirFail'     => 'mkdir fail: ?',
        'serverDirFill' => 'server dir not empty: ?',
        'rmFileFail'    => 'remove file fail: ?',
        'rmDirFail'     => 'remove dir fail: ?',
        'prefixIsFile'  => 'prefix dir is file: ?',
        'connectFail'    => 'connect fail',
        'loginFail'     => 'login fail',
    ];

}