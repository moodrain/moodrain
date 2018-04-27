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
        if($this->prefix && !$this->exist($this->prefix, false))
            $this->mkdir($this->prefix, false);
        if(!$this->exist($this->prefix, false))
            $this->error = 'prefix dir not exists';
        return $this->conn ? $this : false;
    }
    public function prefix(string $prefix = null)
    {
        if($prefix)
        {
            if(!$this->exist($prefix, false))
                $this->mkdir($prefix, false);
            if(!$this->exist($prefix, false))
                $this->error = 'prefix dir not exists';
            $this->prefix = $prefix;
            return $this;
        }
        else
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
        else
            return $this->local;
    }
    public function server($server = null)
    {
        if($server)
        {
            $this->server = $server;
            return $this;
        }
        else
            return $this->server;
    }
    public function error() : string
    {
        return $this->error;
    }

    public function exist(string $file = null, bool $withPrefix = true) : bool
    {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        $list = @ftp_nlist($this->conn, dirname($file));
        $list = $list === false ? [] : $list;
        $file = substr($file, -1) == '/' ? substr($file, 0, strlen($file) - 1) : $file;
        return in_array($file, $list);
    }
    public function list(string $dir = null, bool $withPrefix = true)
    {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? ($this->prefix . $dir) : $dir;
        return @ftp_nlist($this->conn, $dir);
    }
    public function get(string $file = null, $local = null, bool $withPrefix = true) : bool
    {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        $local = $local ?? $this->local ?? basename($file);
        if(!$this->exist($file, false))
            $this->error = 'server file not found';
        else if(is_resource($local))
            return @ftp_fget($this->conn, $local, $file, FTP_BINARY);
        else if(file_exists($local) && !$this->force)
            $this->error = 'local file already exists';
        else if(!file_exists(dirname($local)))
            Tool::mkdir(dirname($local));
        return $this->error === '' ? @ftp_get($this->conn, $local, $file, FTP_BINARY) : false;
    }
    public function put($local = null, string $file = null, bool $withPrefix = true) : bool
    {
        $local = $local ?? $this->local;
        $file = $file ?? $this->server ?? basename($local);
        $file = ($this->prefix && $withPrefix) ? ($this->prefix . $file) : $file;
        if(is_resource($local))
        {
            if($this->exist($file) && !$this->force)
                $this->error = 'server file already exists';
            else if(!$this->exist(dirname($file), false) &&  $this->mkdir(dirname($file), false))
                return @ftp_fput($this->conn, $file, $local, FTP_BINARY);
        }
        else
        {
            if(!file_exists($local))
                $this->error = 'local file not found';
            else if($this->exist($file, false) && !$this->force)
                $this->error = 'server file already exists';
            else if(!$this->exist(dirname($file)) &&  $this->mkdir(dirname($file), false))
                return @ftp_put($this->conn, $file, $local, FTP_BINARY);
        }
        return $this->error === '';
    }
    public function mkdir(string $dir = null, bool $withPrefix = true) : bool
    {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$this->exist(dirname($dir), false))
            $this->mkdir(dirname($dir), false);
        if($this->exist($dir, false) && !$this->force)
                $this->error = 'server dir already exists';
        else
            return @ftp_mkdir($this->conn, $dir);
        return $this->error === '';
    }
    public function rmdir(string $dir = null, bool $withPrefix = true) : bool
    {
        $dir = $dir ?? $this->server;
        $dir = ($this->prefix && $withPrefix) ? $this->prefix . $dir : $dir;
        if(!$this->exist($dir, false) && !$this->force)
                $this->error = 'server dir not exists';
        else
        {
            $files = $this->list($dir, false);
            if(count($files) > 0)
            {
                if($this->force)
                {
                    foreach($files as $file)
                        if(!@ftp_delete($this->conn, $file))
                            $this->rmdir($file, false);
                    if(!@ftp_rmdir($this->conn, $dir))
                        $this->error = 'error in recursion rmdir';
                }
                else
                    $this->error = 'server dir not empty';
            }
            else
                return @ftp_rmdir($this->conn, $dir);
        }
        return $this->error === '';
    }
    public function del(string $file = null, bool $withPrefix = true) : bool
    {
        $file = $file ?? $this->server;
        $file = ($this->prefix && $withPrefix) ? $this->prefix . $file : $file;
        if(!$this->exist($file, false) && !$this->force)
            $this->error = 'server file not found';
        else
            return @ftp_delete($this->conn, $file);
        return $this->error === '';
    }
    public function close() : void
    {
        ftp_close($this->conn);
    }
    public function __destruct()
    {
        $this->close();
    }
}