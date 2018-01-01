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

    public function __construct(Array $config  = [])
    {
        $conf = new Config();
        $this->host = $config['host'] ?? $conf('ftp.host');
        $this->port = $config['port'] ?? $conf('ftp.port', 22);
        $this->user = $config['user'] ?? $conf('ftp.user');
        $this->pass = $config['pass'] ?? base64_decode($conf('ftp.pass'));
        $this->ssl = $config['ssl'] ?? $conf('ftp.ssl', false);
        $this->prefix = $config['prefix'] ?? $conf('ftp.prefix', null);
        $this->force = false;
        $this->conn = $this->ssl ? ftp_ssl_connect($this->host, $this->port) : ftp_connect($this->host, $this->port);
        ftp_login($this->conn, $this->user, $this->pass);
    }
    public function prefix($prefix = null)
    {
        if($prefix)
        {
            $this->prefix = $prefix;
            return $this;
        }
        else
            return $this->prefix;
    }
    public function force($force = null)
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
    public function error()
    {
        return $this->error;
    }

    public function exist($file = null)
    {
        $file = $file ?? $this->server;
        $file = $this->prefix ? $this->prefix . '/' . $file : $file;
        $result = @ftp_nlist($this->conn, $file);
        return $result || $result === [] ? true : false;
    }
    public function list($dir = null)
    {
        $dir = $dir ?? $this->server;
        $dir = $this->prefix ? $this->prefix . '/' . $dir : $dir;
        $files = @ftp_nlist($this->conn, $dir);
        $files = $files == false ? [] : $files;
        return $files;
    }
    public function get($file = null, $local = null)
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
    public function put($file = null, $local = null)
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
    public function mkdir($dir = null)
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
    public function rmdir($dir = null)
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
    public function del($file = null)
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
    public function close()
    {
        ftp_close($this->conn);
    }
    public function __destruct()
    {
        $this->close();
    }
}