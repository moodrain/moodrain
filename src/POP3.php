<?php
namespace Muyu;

class POP3
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $path;

    private $box;
    private $mails = [];
    private $after;

    public function __construct(Array $config = [])
    {
        $conf = new Config();
        $this->host = $config['host'] ?? $conf('pop3.host');
        $this->port = $config['port'] ?? $conf('pop3.port');
        $this->user = $config['user'] ?? $conf('pop3.user');
        $this->pass = $config['pass'] ?? $conf('pop3.pass');
        $this->path = $config['path'] ?? $conf('pop3.path', null);
        $this->after = $config['after'] ?? $conf('pop3.after', null) ?? date('Y-m-d H:i:s', strtotime('-7 days'));
        date_default_timezone_set('Asia/Shanghai');
        $host = '{'. $this->host . ':' . $this->port . '/pop/ssl}INBOX';
        if($this->path && !file_exists($this->path))
            mkdir($this->path);
        $this->box = new \PhpImap\Mailbox($host, $this->user, $this->pass, $this->path);
        foreach(array_reverse($this->box->getMailsInfo($this->box->searchMailbox())) as $mailInfo)
        {
            $mail = [];
            $mail['id'] = $mailInfo->uid;
            $mail['subject'] = $mailInfo->subject;
            $mail['writer'] = explode(' <', $mailInfo->from)[0] ?? 'writer';
            $mail['from'] = substr(explode(' <', $mailInfo->from)[1] ?? 'fromm', 0, -1);
            $mail['to'] = $mailInfo->to;
            $mail['date'] = date('Y-m-d H:i:s', $mailInfo->udate);
            $this->mails[] = $mail;
        }
    }
    public function list()
    {
        return $this->mails;
    }
    public function mails($read = true)
    {
        $news = [];
        for($i = 0;$i < count($this->mails);$i++)
        {
            if($this->mails[$i]['date'] <= $this->after)
                break;
            $news[] = $this->mails[$i] = $read ? $this->get($i) : $this->mails[$i];
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
        else
            return $this->after;
    }
    public function get($index = 0)
    {
        if(!($id = $this->mails[$index]['id'] ?? null))
            return null;
        $mailInfo = $this->box->getMail($id);
        $mail = [];
        $mail['id'] = $id;
        $mail['subject'] = $mailInfo->subject;
        $mail['writer'] = $mailInfo->fromName;
        $mail['from'] = $mailInfo->fromAddress;
        $mail['to'] = $mailInfo->toString;
        $mail['date'] = $mailInfo->date;
        $mail['text'] = $mailInfo->textPlain;
        $mail['html'] = $mailInfo->textHtml;
        $files = [];
        $filesInfo = $mailInfo->getAttachments();
        foreach($filesInfo as $fileInfo)
        {
            if($this->path)
            {
                $dir = $this->path . '/' . $mail['id'];
                $old = $this->path . '/' . $mail['id'] . '_' . $fileInfo->id . '_.' . Tool::ext($fileInfo->name);
                $new = $dir . '/' . $fileInfo->name;
                if(!file_exists($dir))
                    mkdir($dir);
                rename($old, $new);
            }
            $files[] = $fileInfo->name;
        }
        $mail['file'] = $files;
        return $mail;
    }
    public function del($index)
    {
        if(!($id = $this->mails[$index]['id'] ?? null))
            return false;
        $id = $this->mails[$index]['id'];
        $this->box->deleteMail($id);
        return true;
    }
    public function close()
    {
        $this->box->disconnect();
    }
    public function __destruct()
    {
        $this->close();
    }
}