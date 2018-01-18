<?php
namespace Muyu;

class FTP
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $ssl;
    private $prefix;
    private $conn;
    private $force;
    private $local;
    private $server;
    private $error;

    public function __construct(string $muyuConfig = 'ftp')
    {
        $config = new Config();
        $this->init($config($muyuConfig));
    }
    public function init(array $config  = []) : FTP
    {
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->pass = base64_decode($config['pass'] ?? '');
        $this->force = false;
        $this->ssl = false;
        $this->conn = $this->ssl ? ftp_ssl_connect($this->host, $this->port) : ftp_connect($this->host, $this->port);
        ftp_login($this->conn, $this->user, $this->pass);
        return $this;
    }
    public function prefix(string $prefix = null)
    {
        if($prefix)
        {
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
    public function local(string $local = null)
    {
        if($local)
        {
            $this->local = $local;
            return $this;
        }
        else
            return $this->local;
    }
    public function server(string $server = null)
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

    public function exist(string $file = null) : bool
    {
        $file = $file ?? $this->server;
        $file = $this->prefix ? $this->prefix . '/' . $file : $file;
        $result = @ftp_nlist($this->conn, $file);
        return $result || $result === [] ? true : false;
    }
    public function list(string $dir = null) : array
    {
        $dir = $dir ?? $this->server;
        $dir = $this->prefix ? $this->prefix . '/' . $dir : $dir;
        $files = @ftp_nlist($this->conn, $dir);
        $files = $files == false ? [] : $files;
        return $files;
    }
    public function get(string $file = null, string $local = null) : bool
    {
        $file = $file ?? $this->server;
        $local = $local ?? $this->local;
        if(!$this->exist($file))
        {
            $this->error = 'server file not found';
            return false;
        }
        $file = $this->prefix ? $this->prefix . '/' . $file : $file;
        $local = $local ?? pathinfo($file)['basename'];
        if(is_resource($local))
            return @ftp_fget($this->conn, $local, $file, FTP_BINARY);
        else
        {
            if(!file_exists($local) || $this->force)
                return @ftp_get($this->conn, $local, $file, FTP_BINARY);
            else
            {
                $this->error = 'local file already exists';
                return false;
            }
        }
    }
    public function put(string $local = null, string $file = null) : bool
    {
        $file = $file ?? $this->server ?? $this->local;
        $local = $local ?? $this->local;
        if($this->exist($file) && !$this->force)
        {
            $this->error = 'server file already exists';
            return false;
        }
        $dir = pathinfo($file)['dirname'];
        if(!$this->exist($dir))
            $this->mkdir($dir);
        $file = $this->prefix ? $this->prefix . '/' . $file : $file;
        $local = $local ?? pathinfo($file)['basename'];
        if(!file_exists($local))
        {
            $this->error = 'local file not found';
            return false;
        }
        if(is_resource($local))
            return @ftp_fput($this->conn, $file, $local, FTP_BINARY);
        else
            return @ftp_put($this->conn, $file, $local, FTP_BINARY);
    }
    public function mkdir(string $dir = null) : bool
    {
        $dir = $dir ?? $this->server;
        if($this->exist($dir))
        {
            if($this->force)
                return true;
            else
            {
                $this->error = 'server dir already exists';
                return false;
            }
        }
        else
        {
            $dir = $this->prefix ? $this->prefix . '/' . $dir : $dir;
            return @ftp_mkdir($this->conn, $dir);
        }
    }
    public function rmdir(string $dir = null) : bool
    {
        $dir = $dir ?? $this->server;
        if(!$this->exist($dir))
        {
            if($this->force)
                return true;
            else
            {
                $this->error = 'server dir not exists';
                return false;
            }
        }
        else
        {
            $files = $this->list($dir);
            if(count($files) > 0)
            {
                if($this->force)
                {
                    foreach($files as $file)
                        $rs = @ftp_delete($this->conn, $file);
                    $dir = $this->prefix ? $this->prefix . '/' . $dir : $dir;
                    if(!@ftp_rmdir($this->conn, $dir))
                    {
                        $this->error = 'rm dir fail, this dir has dir';
                        return false;
                    }
                    else
                        return true;
                }
                else
                {
                    $this->error = 'server dir not empty';
                    return false;
                }
            }
            else
            {
                $dir = $this->prefix ? $this->prefix . '/' . $dir : $dir;
                return @ftp_rmdir($this->conn, $dir);
            }
        }
    }
    public function del(string $file = null) : bool
    {
        $file = $file ?? $this->server;
        if(!$this->exist($file))
        {
            if($this->force)
                return true;
            else
            {
                $this->error = 'server file not found';
                return false;
            }
        }
        else
        {
            $file = $this->prefix ? $this->prefix . '/' . $file : $file;
            return @ftp_delete($this->conn, $file);
        }
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