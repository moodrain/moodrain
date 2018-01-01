<?php
namespace Muyu;

class Command
{
    private $host;
    private $username;
    private $password;
    private $options;

    private function command4OneParam(Array $argv)
    {
        $command = strtolower($argv[0]);
        switch($command)
        {
            case 'encodepass'       : $this->encodePass();break;
            case 'listmuyujson'     : $this->listMuyuJson();break;
            case 'downloadmuyujson' : $this->downloadMuyuJson();break;
            case 'uploadmuyujson'   : $this->uploadMuyuJson();break;
            case 'removemuyujson'   : $this->removeMuyuJson();break;
            case 'osslist'          : $this->ossList('/');break;
            case 'ftplist'          : $this->ftpList(null);break;
            case 'maillist'         : $this->mailList();break;
            case 'mailread'         : $this->mailRead();break;
            case 'maildel'          : $this->mailDel();break;
            case 'mailsend'         : $this->mailSend();break;
            default                 : echo 'unknown command';
        }
    }
    private function command4TwoParam(Array $argv)
    {
        $command = strtolower($argv[0]);
        switch($command)
        {
            case 'downloadmuyujson' : $this->downloadMuyuJson($argv[1]);break;
            case 'uploadmuyujson'   : $this->uploadMuyuJson($argv[1]);break;
            case 'removemuyujson'   : $this->removeMuyuJson($argv[1]);break;
            case 'ftplist'          : $this->ftpList($argv[1]);break;
            case 'ftpget'          : $this->ftpGet($argv[1], null);break;
            case 'ftpput'           : $this->ftpPut($argv[1], null);break;
            case 'ftpdel'           : $this->ftpDel($argv[1]);break;
            case 'ftpmkdir'         : $this->ftpMkdir($argv[1]);break;
            case 'ftprmdir'         : $this->ftpRmdir($argv[1]);break;
            case 'osslist'          : $this->ossList($argv[1]);break;
            case 'ossget'           : $this->ossGet($argv[1]);break;
            case 'ossput'           : $this->ossPut($argv[1], null);break;
            case 'ossdel'           : $this->ossDel($argv[1]);break;
            case 'maillist'         : $this->mailList($argv[1]);break;
            case 'mailread'         : $this->mailRead($argv[1]);break;
            case 'maildel'          : $this->mailDel($argv[1]);break;
            default                 : echo 'unknown command';
        }
    }
    private function command4ThreeParam(Array $argv)
    {
        $command = strtolower($argv[0]);
        switch($command)
        {
            case 'ossput'   : $this->ossPut($argv[1], $argv[2]);break;
            case 'ftpget'   : $this->ftpGet($argv[1], $argv[2]);break;
            case 'ftpput'   : $this->ftpPut($argv[1], $argv[2]);break;
            case 'mailread' : $this->mailRead($argv[1], $argv[2]);break;
            default         : echo 'unknown command';
        }
    }
    private function command4FourParam(Array $argv)
    {
        $command = strtolower($argv[0]);
        switch($command)
        {
            default       : echo 'unknown command';
        }
    }

    private function encodePass()
    {
        $pass = $this->password();
        echo base64_encode($pass);
    }
    private function listMuyuJson()
    {
        $username = $this->username ?? $this->readLine('username');
        $password = $this->password ?? $this->password('password');
        $result = (new Curl())->url($this->host . '/api/moodrain/listMuyuJson')->data([
            'username' => $username,
            'password' => $password,
        ])->receive('json')->post();
        $this->response($result);
    }
    private function downloadMuyuJson($file = null)
    {
        $file = $file ?? $this->readLine('muyuJsonName');
        $username = $this->username ?? $this->readLine('username');
        $password = $this->password ?? $this->password('password');
        $override = $this->optHas('f');
        $result = (new Curl())->url($this->host . '/api/moodrain/downloadMuyuJson/' . $file)->data([
            'username' => $username,
            'password' => $password,
        ])->receive('json')->post();
        $this->response($result, function($result) use ($override) {
            if(isset($result['data']))
            {
                if($override)
                    file_put_contents('muyu.json', $result['data']);
                else
                {
                    if(file_exists('muyu.json'))
                    {
                        if($this->readLine('muyu.json already exists, override? (n)') == 'y')
                            file_put_contents('muyu.json', $result['data']);
                    }
                    else
                        file_put_contents('muyu.json', $result['data']);
                }
            }
        });
    }
    private function uploadMuyuJson($file = null)
    {
        $muyuJson = file_get_contents('muyu.json');
        if(json_decode($muyuJson) == null)
        {
            echo 'invalid json format' . PHP_EOL;
            exit(1);
        }
        $file = $file ?? $this->readLine('muyuJsonName');
        $username = $this->username ?? $this->readLine('username');
        $password = $this->password ?? $this->password('password');
        $override = $this->optHas('f');
        $result = (new Curl())->url($this->host . '/api/moodrain/uploadMuyuJson/' . $file . ($override ? '/override' : ''))->data([
            'username' => $username,
            'password' => $password,
            'data' => $muyuJson,
        ])->receive('json')->post();
        $this->response($result, function($result) use ($file, $username, $password){
            if($result['msg'] == 'file already exists')
                if($this->readLine(', override? (n)') == 'y')
                {
                    $result = (new Curl())->url($this->host . '/api/moodrain/uploadMuyuJson/' . $file . '/override')->data([
                        'username' => $username,
                        'password' => $password,
                        'data' => file_get_contents('muyu.json'),
                    ])->receive('json')->post();
                    $this->response($result);
                }
        });
    }
    private function removeMuyuJson($file = null)
    {
        $file = $file ?? $this->readLine('muyuJsonName');
        $username = $this->username ?? $this->readLine('username');
        $password = $this->password ?? $this->password('password');
        $result = (new Curl())->url($this->host . '/api/moodrain/removeMuyuJson/' . $file)->data([
            'username' => $username,
            'password' => $password,
        ])->receive('json')->post();
        $this->response($result);
    }
    private function ftpList($dir)
    {
        $ftp = new FTP();
        $files = $ftp->list($dir);
        $prefix = $ftp->prefix() ? $ftp->prefix() . '/' . ($dir ? $dir . '/' : '') : $dir . '/';
        foreach($files as $file)
            echo ($prefix ? str_replace($prefix, '', $file) : $file) . PHP_EOL;
        $ftp->close();
    }
    private function ftpGet($file, $local)
    {
        $ftp = new FTP();
        $local = $local ?? $file;
        if($this->optHas('f'))
            $ftp->enforce()->get($file, $local);
        else
        {
            if(file_exists($local))
            {
                if($this->readLine('local file already exists, override? (n)') == 'y')
                    $ftp->enforce()->get($file, $local);
            }
            else
                $ftp->get($file, $local);
        }
        echo $ftp->error();
        $ftp->close();
    }
    private function ftpPut($local, $file)
    {
        $ftp = new FTP();
        $file = $file ?? $local;
        if($this->optHas('f'))
            $ftp->enforce()->put($local, $file);
        else
        {
            if($ftp->exist($file))
            {
                if($this->readLine('server file already exists, override? (n)') == 'y')
                    $ftp->enforce()->put($local, $file);
            }
            else
                $ftp->put($local, $file);
        }
        echo $ftp->error();
        $ftp->close();
    }
    private function ftpDel($file)
    {
        $ftp = new FTP();
        $ftp->del($file);
        echo $ftp->error();
        $ftp->close();
    }
    private function ftpMkdir($dir)
    {
        $ftp = new FTP();
        $ftp->mkdir($dir);
        echo $ftp->error();
    }
    private function ftpRmdir($dir)
    {
        $ftp = new FTP();
        if($this->optHas('f'))
            $ftp->enforce()->rmdir($dir);
        else
        {
            if(count($ftp->list($dir)) > 0)
            {
                if($this->readLine('server dir not empty, rm dir? (n)') == 'y')
                    $ftp->enforce()->rmdir($dir);
            }
            else
                $ftp->rmdir($dir);
        }
        echo $ftp->error();
        $ftp->close();
    }
    private function ossPut($from, $to)
    {
        if($to == null)
            $to = $from;
        else if(substr($to, -1) == '/')
            $to = $to . $from;
        $oss = new OSS();
        $list = $oss->list($to);
        if($list && !$this->optHas('f'))
        {
            if($this->readLine('already exists, override? (n)') == 'y')
                $oss->put($from, $to);
        }
        else
            $oss->put($from, $to);
    }
    private function ossList($path)
    {
        $oss = new OSS();
        $files = $oss->list($path);
        if($files)
        {
            foreach($files as $file)
            {
                $isDir = substr($file['Key'], -1) == '/';
                if(!$isDir)
                    echo PHP_EOL . str_replace($path . '/', '', $file['Key']) . ' ' . date('Y-m-d H:i:s', strtotime($file['LastModified']));
            }
            echo PHP_EOL;
        }
        else
            echo 'list fail';
    }
    private function ossGet($file)
    {
        $oss = new OSS();
        $basename = pathinfo($file)['basename'];
        $file = $oss->get($file);
        if($file)
        {
            $result = XML::parse($file);
            if($result && $result['Code'] == 'NoSuchKey')
                echo 'file not exists';
            else
            {
                if(file_exists($basename) && !$this->optHas('f'))
                {
                    if($this->readLine($basename . ' already exists, override?(n)') == 'y')
                        file_put_contents($basename, $file);
                }
                else
                    file_put_contents($basename, $file);
            }
        }
        else
            echo 'download fail';
    }
    private function ossDel($file)
    {
        $oss = new OSS();
        if(!$oss->del($file))
            echo 'delete fail';
    }
    private function mailList($days = null)
    {
        $days = intval($days ?? $this->readLine('list mails before days (0)'));
        $pop = new POP3();
        $mails = $pop->after(date('Y-m-d 00:00:00', strtotime('-' . $days . ' days')))->mails(false);
        $index = 0;
        foreach($mails as $mail)
            echo PHP_EOL . $index++ . ': ' . $mail['writer'] . '  ' . $mail['subject'] . PHP_EOL . $mail['from'] . '  ' . $mail['date'] . PHP_EOL;
        if(empty($mails))
            echo 'no new mail';
    }
    private function mailRead($index = null, $receiveFile = false)
    {
        $index = intval($index ?? $this->readLine('the index of mail to read (0)'));
        $pop = new POP3(['path' => $receiveFile ? 'mailAttachments' : null]);
        $mail = $pop->get($index);
        if(!$mail)
            echo 'mail not found';
        else
            echo PHP_EOL .
                'Subject: ' . $mail['subject'] . PHP_EOL .
                'From:    ' . $mail['writer'] . '<' . $mail['from'] . '>' . PHP_EOL .
                'Date:    ' . $mail['date'] . PHP_EOL .
                'Content: ' . $mail['text'] . PHP_EOL . PHP_EOL .
                'Attachments:' . join(', ', $mail['file']);
    }
    private function mailDel($index = null)
    {
        $index = intval($index ?? $this->readLine('the index of mail to delete (0)'));
        $pop = new POP3();
        if(!$pop->del($index))
            echo 'mail not found';
    }
    private function mailSend()
    {
        $subject = $this->readLine('Subject');
        $content = $this->readLine('Content');
        $to = $this->readLine('Send to');
        $smtp = new SMTP();
        $smtp->subject($subject)->html('<p>' . $content . '</p>')->text($content)->to($to)->send();
    }

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
        else if(count($argv) == 3)
            $this->command4ThreeParam($argv);
        else if(count($argv) == 4)
            $this->command4FourParam($argv);
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
    private function readLine($echo = null)
    {
        if($echo)
            echo $echo . ': ';
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