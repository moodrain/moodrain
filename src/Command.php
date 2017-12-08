<?php
namespace Muyu;

class Command
{
    private $host;
    private $username;
    private $password;
    private $options;

    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
    }
    public function read(Array $argv, Array $options)
    {
        array_shift($argv);
        foreach($options as $option)
            array_shift($argv);
        $this->options = $options;
        if(count($argv) == 1)
            $this->command4OneParam($argv);
        else if(count($argv) == 2)
            $this->command4TwoParam($argv);
    }
    private function command4OneParam(Array $argv)
    {
        switch($argv[0])
        {
            case 'listMuyuJson' : $this->listMuyuJson();break;
            case 'downloadMuyuJson' : $this->downloadMuyuJson();break;
            case 'uploadMuyuJson' : $this->uploadMuyuJson();break;
            case 'removeMuyuJson' : $this->removeMuyuJson();break;
        }
    }
    private function command4TwoParam(Array $argv)
    {
        switch($argv[0])
        {
            case 'downloadMuyuJson' : $this->downloadMuyuJson($argv[1]);break;
            case 'uploadMuyuJson' : $this->uploadMuyuJson($argv[1]);break;
            case 'removeMuyuJson' : $this->removeMuyuJson($argv[1]);break;
        }
    }

    private function listMuyuJson()
    {
        $username = $this->username ?? $this->readline('username');
        $password = $this->password ?? $this->password('password');
        $result = (new Curl())->url($this->host . '/api/moodrain/listMuyuJson')->data([
            'username' => $username,
            'password' => $password,
        ])->receive('json')->post();
        $this->response($result);
    }
    private function downloadMuyuJson($filename = null)
    {
        $filename = $filename ?? $this->readline('muyuJsonName');
        $username = $this->username ?? $this->readline('username');
        $password = $this->password ?? $this->password('password');
        $override = $this->optHas('f');
        $result = (new Curl())->url($this->host . '/api/moodrain/downloadMuyuJson/' . $filename)->data([
            'username' => $username,
            'password' => $password,
        ])->receive('json')->post();
        $this->response($result, function($result) use ($override) {
            if($override)
                file_put_contents('muyu.json', $result['data']);
            else if($this->readline('muyu.json already exists, override? (n)') == 'y')
                file_put_contents('muyu.json', $result['data']);
        });
    }
    private function uploadMuyuJson($filename = null)
    {
        $filename = $filename ?? $this->readline('muyuJsonName');
        $username = $this->username ?? $this->readline('username');
        $password = $this->password ?? $this->password('password');
        $override = $this->optHas('f');
        $result = (new Curl())->url($this->host . '/api/moodrain/uploadMuyuJson/' . $filename . ($override ? '/override' : ''))->data([
            'username' => $username,
            'password' => $password,
            'data' => file_get_contents('muyu.json'),
        ])->receive('json')->post();
        $this->response($result, function($result) use ($filename, $username, $password){
            if($result['msg'] == 'file already exists')
                if($this->readline(', override? (n)') == 'y')
                {
                    $result = (new Curl())->url($this->host . '/api/moodrain/uploadMuyuJson/' . $filename . '/override')->data([
                        'username' => $username,
                        'password' => $password,
                        'data' => file_get_contents('muyu.json'),
                    ])->receive('json')->post();
                    $this->response($result);
                }
        });
    }
    private function removeMuyuJson($filename = null)
    {
        $filename = $filename ?? $this->readline('muyuJsonName');
        $username = $this->username ?? $this->readline('username');
        $password = $this->password ?? $this->password('password');
        $result = (new Curl())->url($this->host . '/api/moodrain/removeMuyuJson/' . $filename)->data([
            'username' => $username,
            'password' => $password,
        ])->receive('json')->post();
        $this->response($result);
    }

    private function response(Array $result = null, callable $callback = null)
    {
        if($result)
        {
            if(isset($result['msg']))
                echo $result['msg'];
            if($callback)
                $callback($result);
        }
    }
    private function optHas($option)
    {
        return isset($this->options[$option]);
    }
    private function optVal($option)
    {
        return $this->options[$option] ?? null;
    }
    private function readline($echo = null)
    {
        if($echo)
            echo $echo . ':';
        return trim(fgets(STDIN));
    }
    private function password($echo = 'password')
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
        {
            $password = shell_exec('C:\Windows\system32\WindowsPowerShell\v1.0\powershell.exe -Command "$Password=Read-Host -assecurestring \"'. $echo .'\" ; $PlainPassword = [System.Runtime.InteropServices.Marshal]::PtrToStringAuto([System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($Password)) ; echo $PlainPassword;"');
            return substr(str_replace("\n", '', $password), 3);
        }
        else
        {
            echo 'password: ';
            system('stty -echo');
            $password = rtrim(fgets(STDIN), PHP_EOL);
            system('stty echo');
            echo PHP_EOL;
            return $password;
        }
    }
}