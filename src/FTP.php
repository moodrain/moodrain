<?php
namespace Muyu;

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
    private $error = '';

    public function __construct(string $muyuConfig = 'ftp.default', bool $init = true)
    {
        $config = new Config();
        if($init)
            $this->init($config($muyuConfig));
    }
    public function init(array $config  = [])
    {
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->pass = base64_decode($config['pass'] ?? '');
        $this->force = false;
        $this->conn = $this->ssl ? ftp_ssl_connect($this->host, $this->port) : ftp_connect($this->host, $this->port);
        ftp_login($this->conn, $this->user, $this->pass);
        if($this->pasv)
            ftp_pasv($this->conn, true);
        if($this->prefix)
        {
            $type = $this->type($this->prefix, false);
            if(!$type)
                $this->mkdir($this->prefix, false);
            else if($type == 'file')
                $this->error = 'prefix name is file';
        }
        return $this->conn && !$this->error ? $this : false;
    }
    public function prefix(string $prefix = null)
    {
        if($prefix)
        {
            $type = $this->type($prefix, false);
            if(!$type)
                $this->mkdir($this->prefix, false);
            else if($type == 'file')
                $this->error = 'prefix name is file';
            if(!$this->error)
                $this->prefix = $prefix;
            else
                return false;
            return $this;
        }
        return $this->prefix;
    }
    public function force(bool $force = null)
    {
        if($force === null)
            return $this->force;
        else
        {
            $this->force = $force;
            return $this;
        }
    }
    public function enforce()
    {
        $this->force = true;
        return $this;
    }
    public function safe()
    {
        $this->force = false;
        return $this;
    }
    public function local($local = null)
    {
        if($local)
        {
            $this->local = $local;
            return $this;
        }
        return $this->local;
    }
    public function server($server = null)
    {
        if($server)
        {
            $this->server = $server;
            return $this;
        }
        return $this->server;
    }
    public function error() : string
    {
        return $this->error;
    }
    public function type(string $file = null, bool $withPrefix = true) : ?string
    {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        if(!$file)
        {
            $this->error = 'dir not set';
            return false;
        }
        $ll = $this->ll(dirname($file), false);
        $exist = false;
        $type  = null;
        foreach($ll as $l)
            if($l[0] == basename($file))
            {
                $exist = true;
                $type = $l[1];
                break;
            }
        return $exist ? $type : null;
    }
    public function list(string $dir = null, bool $withPrefix = true)
    {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? ($this->prefix . $dir) : $dir;
        return @ftp_nlist($this->conn, $dir);
    }
    public function ll(string $dir = null, bool $withPrefix = true)
    {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? ($this->prefix . $dir) : $dir;
        $ll = @ftp_rawlist($this->conn, $dir);
        if($ll === false)
            return false;
        $files = [];
        foreach($ll as $l)
        {
            $l = explode(' ', $l);
            $name = $l[count($l)-1];
            $type = $l[0]{0};
            switch($type)
            {
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
    public function get(string $file = null, $local = null, bool $withPrefix = true) : bool
    {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        $local = $local ?? $this->local ?? basename($file);
        if(!$file || !$local)
        {
            $this->error = 'local or server file not set';
            return false;
        }
        $type = $this->type($file, false);
        if(!$type)
            $this->error = 'server file not found';
        else if($type == 'directory')
            $this->error = 'request name is directory';
        else if(is_resource($local))
            return @ftp_fget($this->conn, $local, $file, FTP_BINARY);
        else if(file_exists($local) && !$this->force)
            $this->error = 'local file already exists';
        else if(!file_exists(dirname($local)))
            Tool::mkdir(dirname($local));
        if(!$this->error && !@ftp_get($this->conn, $local, $file, FTP_BINARY))
            $this->error = 'download file fail';
        return $this->error === '' ?  : false;
    }
    public function put($local = null, string $file = null, bool $withPrefix = true) : bool
    {
        $local = $local ?? $this->local;
        $file = $file ?? $this->server ?? basename($local);
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        if(!$file || !$local)
        {
            $this->error = 'local or server file not set';
            return false;
        }
        $isResource = is_resource($local);
        if(!$isResource && !file_exists($local))
        {
            $this->error = 'local file not found';
            return false;
        }
        $type = $this->type($file, false);
        if(!$type)
        {
            $dirType = $this->type(dirname($file), false);
            if(!$dirType)
                $this->mkdir(dirname($file), false);
            else if($dirType == 'file')
                $this->error = 'duplicate name file exists';
        }
        else if($type == 'directory')
            $this->error = 'duplicate name dir exists';
        else if($type == 'file' && !$this->force)
            $this->error = 'server file already exists';
        if(!$this->error && !($isResource ? @ftp_fput($this->conn, $file, $local, FTP_BINARY) : @ftp_put($this->conn, $file, $local, FTP_BINARY)))
            $this->error = 'upload file fail';
        return $this->error === '';
    }
    public function mkdir(string $dir = null, bool $withPrefix = true) : bool
    {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$dir)
        {
            $this->error = 'dir not set';
            return false;
        }
        $type = $this->type($dir, false);
        if($type == 'directory' && !$this->force)
            $this->error = 'server dir already exists';
        else if($type == 'file')
            $this->error = 'duplicate name file exists';
        else if(!$type)
        {
            $parentType = $this->type(dirname($dir), false);
            if(!$parentType && !$this->mkdir(dirname($dir), false))
                return false;
            else if($parentType == 'file')
                $this->error = 'duplicate name file exists';
            else if(!$this->error && !@ftp_mkdir($this->conn, $dir))
                $this->error = 'mkdir fail';
        }
        return $this->error === '';
    }
    public function rmdir(string $dir = null, bool $withPrefix = true) : bool
    {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$dir)
        {
            $this->error = 'dir not set';
            return false;
        }
        $type  = $this->type($dir, false);
        if(!$type && !$this->force)
        {
            $this->error = 'server dir not exists';
            return false;
        }
        $ll = $this->ll($dir, false);
        if(count($ll) && !$this->force)
        {
            $this->error = 'server dir not empty';
            return false;
        }
        foreach($ll as $l)
        {
            $name = $dir . '/' . $l[0];
            $type = $l[1];
            if($type == 'file' && !$this->error && !@ftp_delete($this->conn, $name))
                $this->error = 'del file fail';
            else if($type == 'directory')
                $this->rmdir($name, false);
        }
        if(!$this->error && !@ftp_rmdir($this->conn, $dir))
            $this->error = 'rmdir fail';
        return $this->error === '';
    }
    public function del(string $file = null, bool $withPrefix = true) : bool
    {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        if(!$file)
        {
            $this->error = 'dir not set';
            return false;
        }
        $type = $this->type($file, false);
        if(!$type && !$this->force)
            $this->error = 'server file not found';
        else if($type == 'directory')
            $this->error = 'request name is directory';
        else if($type == 'file' && !@ftp_delete($this->conn, $file))
            $this->error = 'del file fail';
        return $this->error === '';
    }
    public function close() : void
    {
        if(is_resource($this->conn))
            ftp_close($this->conn);
    }
    public function __destruct()
    {
        $this->close();
    }
}