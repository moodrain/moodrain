<?php
namespace Muyu;

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
        if($init)
            $this->init(conf($muyuConfig));
    }
    function init($config  = []) {
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->pass = base64_decode($config['pass'] ?? '');
        $this->force = false;
        $this->conn = $this->ssl ? ftp_ssl_connect($this->host, $this->port) : ftp_connect($this->host, $this->port);
        ftp_login($this->conn, $this->user, $this->pass);
        if($this->pasv)
            ftp_pasv($this->conn, true);
        if($this->prefix) {
            $type = $this->type($this->prefix, false);
            if(!$type)
                $this->mkdir($this->prefix, false);
            else if($type == 'file')
                $this->addError(13, 'prefix name is file');
        }
        return $this->conn && $this->error->ok() ? $this : false;
    }
    function prefix($prefix = null) {
        if($prefix) {
            $type = $this->type($prefix, false);
            if(!$type)
                $this->mkdir($this->prefix, false);
            else if($type == 'file')
                $this->addError(13, 'prefix name is file');
            if($this->error->ok())
                $this->prefix = $prefix;
            else
                return false;
            return $this;
        }
        return $this->prefix;
    }
    function force($force = null) {
        if($force === null)
            return $this->force;
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
        if(!$local)
           return $this->local;
        $this->local = $local;
        return $this;
    }
    function server($server = null) {
        if(!$server)
            return $this->server;
        $this->server = $server;
        return $this;
    }
    function type($file = null, $withPrefix = true) {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        if(!$file) {
            $this->addError(1, 'dir not set');
            return false;
        }
        $ll = $this->ll(dirname($file), false);
        $exist = false;
        $type  = null;
        foreach($ll as $l)
            if($l[0] == basename($file)) {
                $exist = true;
                $type = $l[1];
                break;
            }
        return $exist ? $type : null;
    }
    function list($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? ($this->prefix . $dir) : $dir;
        return @ftp_nlist($this->conn, $dir);
    }
    function ll($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? ($this->prefix . $dir) : $dir;
        $ll = @ftp_rawlist($this->conn, $dir);
        if($ll === false)
            return false;
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
    function get(string $file = null, $local = null, $withPrefix = true) {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        $local = $local ?? $this->local ?? basename($file);
        if(!$file || !$local) {
            $this->addError(2, 'local or server file not set');
            return false;
        }
        $type = $this->type($file, false);
        if(!$type)
            $this->addError(3, 'server file not found');
        else if($type == 'directory')
            $this->addError(4, 'request name is directory');
        else if(is_resource($local))
            return @ftp_fget($this->conn, $local, $file, FTP_BINARY);
        else if(file_exists($local) && !$this->force)
            $this->addError(5, 'local file already exists');
        else if(!file_exists(dirname($local)))
            Tool::mkdir(dirname($local));
        if($this->error->ok() && !@ftp_get($this->conn, $local, $file, FTP_BINARY))
            $this->addError(6, 'download file fail');
        return $this->error->ok();
    }
    function put($local = null, $file = null, $withPrefix = true) {
        $local = $local ?? $this->local;
        $file = $file ?? $this->server ?? basename($local);
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        if(!$file || !$local) {
            $this->addError(2, 'local or server file not set');
            return false;
        }
        $isResource = is_resource($local);
        if(!$isResource && !file_exists($local)) {
            $this->addError(7, 'local file not found');
            return false;
        }
        $type = $this->type($file, false);
        if(!$type) {
            $dirType = $this->type(dirname($file), false);
            if(!$dirType)
                $this->mkdir(dirname($file), false);
            else if($dirType == 'file')
                $this->addError(8, 'duplicate name file exists');
        }
        else if($type == 'directory')
            $this->addError(9, 'duplicate name dir exists');
        else if($type == 'file' && !$this->force)
            $this->addError(10, 'server file already exists');
        if($this->error->ok() && !($isResource ? @ftp_fput($this->conn, $file, $local, FTP_BINARY) : @ftp_put($this->conn, $file, $local, FTP_BINARY)))
            $this->addError(11, 'upload file fail');
        return $this->error->ok();
    }
    function mkdir($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$dir) {
            $this->addError(1, 'dir not set');
            return false;
        }
        $type = $this->type($dir, false);
        if($type == 'directory' && !$this->force)
            $this->addError(12, 'server dir already exists');
        else if($type == 'file')
            $this->addError(8, 'duplicate name file exists');
        else if(!$type) {
            if($this->prefix == null || $this->prefix == '/') {
                if($this->error->ok() && !@ftp_mkdir($this->conn, $dir))
                    $this->addError(14, 'mkdir fail');
                return $this->error->ok();
            }
            $parentType = $this->type(dirname($dir), false);
            if(!$parentType && !$this->mkdir(dirname($dir), false))
                return false;
            else if($parentType == 'file')
                $this->addError(8, 'duplicate name file exists');
            else if($this->error->ok() && !@ftp_mkdir($this->conn, $dir))
                $this->addError(14, 'mkdir fail');
        }
        return $this->error->ok();
    }
    function rmdir($dir = null, $withPrefix = true) {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$dir) {
            $this->addError(1, 'dir not set');
            return false;
        }
        $type  = $this->type($dir, false);
        if(!$type && !$this->force) {
            $this->addError(15, 'server dir not exists');
            return false;
        }
        $ll = $this->ll($dir, false);
        if(count($ll) && !$this->force) {
            $this->addError(16, 'server dir not empty');
            return false;
        }
        foreach($ll as $l) {
            $name = $dir . '/' . $l[0];
            $type = $l[1];
            if($type == 'file' && $this->error->ok() && !@ftp_delete($this->conn, $name))
                $this->addError(17, 'del file fail');
            else if($type == 'directory')
                $this->rmdir($name, false);
        }
        if($this->error->ok() && !@ftp_rmdir($this->conn, $dir))
            $this->addError(18, 'rmdir fail');
        return $this->error->ok();
    }
    function del($file = null, $withPrefix = true) {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        if(!$file) {
            $this->addError(1, 'dir not set');
            return false;
        }
        $type = $this->type($file, false);
        if(!$type && !$this->force)
            $this->addError(3, 'server file not found');
        else if($type == 'directory')
            $this->addError(4, 'request name is directory');
        else if($type == 'file' && !@ftp_delete($this->conn, $file))
            $this->addError(17, 'del file fail');
        return $this->error->ok();
    }
    function close() {
        if(is_resource($this->conn))
            ftp_close($this->conn);
    }
    function __destruct() {
        $this->close();
    }
}