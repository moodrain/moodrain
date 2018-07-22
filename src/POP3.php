<?php
namespace Muyu;

class POP3
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;
    private $attachPath;
    private $box;
    private $list = [];
    private $mails = [];
    private $after;
    private $getAttach;
    private $error = '';

    public function __construct(string $muyuConfig = 'pop3.default', bool $init = true)
    {
        if($init)
        {
            $config = new Config();
            $this->init($config($muyuConfig));
        }
    }
    public function init(array $config)
    {
        foreach ($config as $key => $val)
            $this->$key = $val;
        $this->pass = base64_decode($config['pass'] ?? '');
        $this->error = 'box not init';
        return $this;
    }
    public function initBox(bool $getAttach = false) : bool
    {
        $this->error = '';
        $this->getAttach = $getAttach;
        Tool::timezone($config['timezone'] ?? 'PRC');
        $host = '{'. $this->host . ':' . ($this->port ?? 995) . '/pop/ssl}INBOX';
        $this->attachPath = $this->path . '/attach';
        if(!file_exists($this->path))
        {
            Tool::mkdir($this->path);
            if(!file_exists($this->path))
            {
                $this->error = 'mail dir not found';
                return false;
            }
        }
        if($getAttach && $this->attachPath && !file_exists($this->attachPath))
        {
            Tool::mkdir($this->attachPath);
            if(!file_exists($this->attachPath))
            {
                $this->error = 'attach dir not found';
                return false;
            }
        }
        $this->box = new \PhpImap\Mailbox($host, $this->user, $this->pass, $getAttach ? $this->attachPath : null);
        $this->list = array_reverse($this->box->searchMailbox('ALL'));
        return true;
    }
    public function list() : array
    {
        return $this->list;
    }
    public function mails(bool $attach = false) : array
    {
        $news = [];
        foreach ($this->list as $mailId)
        {
            $mail = null;
            if(isset($this->mails[$mailId]))
                $mail = $this->mails[$mailId];
            else
                $this->mails[$mailId] = $mail = $this->get($mailId, $attach);
            if(isset($this->after) && $mail <= $this->after)
                break;
            $news[] = $mail;
        }
        return $news;
    }
    public function after($after = null)
    {
        if($after)
        {
            if(is_int($after))
                $after = date('Y-m-d H:i:s', $after);
            else
                $after = strtotime($after) ? date('Y-m-d H:i:s', strtotime($after)) : $this->after;
            $this->after = $after;
            return $this;
        }
        return $this->after;
    }
    public function get(int $id)
    {
        if(!in_array($id, $this->list))
            return [];
        $mailInfo = @$this->box->getMail($id);
        $mail = new \stdClass();
        $mail->id = $id;
        $mail->subject = $mailInfo->subject;
        $mail->writer = $mailInfo->fromName;
        $mail->from = $mailInfo->fromAddress;
        $mail->to = $mailInfo->toString;
        $mail->date = $mailInfo->date;
        $mail->text = $mailInfo->textPlain;
        $mail->html = $mailInfo->textHtml;
        $files = [];
        $filesInfo = @$mailInfo->getAttachments();
        if($this->getAttach)
        {
            foreach($filesInfo as $fileInfo)
            {
                $fileName = $fileInfo->name;
                $replace = [
                    '/\s/' => '_',
                    '/[^0-9a-zа-яіїє_\.]/iu' => '',
                    '/_+/' => '_',
                    '/(^_)|(_$)/' => '',
                ];
                $dir = $this->attachPath . '/' . $mail->id;
                $old = $this->attachPath . '/' . $mail->id . '_' . $fileInfo->id . '_' . preg_replace(array_keys($replace), $replace, $fileName);
                $new = $dir . '/' . $fileName;
                if(!file_exists($dir))
                {
                    Tool::mkdir($dir);
                    if(!file_exists($old) || !file_exists($dir))
                    {
                        $this->error = 'attach remove error';
                        return false;
                    }
                }
                @rename($old, $new);
                $files[] = $new;
            }
        }
        $mail->file = $files;
        return $mail;
    }
    public function del(int $id) : bool
    {
        if(!in_array($id, $this->list))
        {
            $this->error = 'mail not found';
            return false;
        }
        if(isset($this->mails[$id]))
            $this->mails[$id] = null;
        $this->box->deleteMail($id);
        return true;
    }
    public function path() : string
    {
        return $this->path;
    }
    public function attachPath() : string
    {
        return $this->attachPath;
    }
    public function box()
    {
        return $this->box;
    }
    public function close() : void
    {
        if($this->box)
            $this->box->disconnect();
    }
    public function error()
    {
        return $this->error;
    }
    public function __destruct()
    {
        $this->close();
    }
}