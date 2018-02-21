<?php
namespace Muyu;

class POPStore
{
    private $path;
    private $attachPath;
    private $list = [];
    private $pop3;
    private $error = '';

    public function __construct(string $muyuConfig = 'pop3.default', bool $init = true)
    {
        $config = new Config();
        if($init)
            $this->init($config($muyuConfig));
    }
    public function init(array $config) : POPStore
    {
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->pop3 = new POP3('pop3.default', false);
        $this->pop3->init($config);
        $this->pop3->initBox(true);
        $this->attachPath = $this->pop3->attachPath();
        $this->initList();
        $this->updateBox();
        return $this;
    }
    public function list() : array
    {
        return $this->list;
    }
    public function mailsInPage(int $offset, int $limit) : array
    {
        $mails = $this->list;
        foreach($mails as & $mail)
            $mail->content = $this->getMail($mail->id);
        return array_slice($mails, $offset, $limit);
    }
    public function mailsInDate(string $begin, string $end) : array
    {
        $begin = strtotime($begin);
        $end = strtotime($end);
        $mails = [];
        foreach($this->list as $aMail)
            if($aMail->date > $begin && $aMail->date < $end)
                $mails[] = $aMail;
        foreach($mails as & $mail)
            $mail->content = $this->getMail($mail->id);
        return $mails;
    }
    public function path() : string
    {
        return $this->path;
    }
    public function attachPath() : string
    {
        return $this->attachPath;
    }
    public function getMail(int $id)
    {
        $mail = null;
        foreach($this->list as $aMail)
            if($aMail->id == $id)
            {
                $mail = $aMail;
                break;
            }
        if(!$mail)
            return null;
        $mail = json_decode(file_get_contents($this->path . '/' . $mail->id . '_' . $mail->date . '.json'));
        foreach($mail->file as & $file)
        {
            $aFile = new class
            {
                public $name;
                public $ext;
                private $path;
                public function path()
                {
                    return $this->path;
                }
                public function create(int $id, string $attachPath, string $file)
                {
                    $this->name = $file;
                    $this->ext = Tool::ext($file);
                    $this->path = $attachPath . '/' . $id . '/' . $file;
                    return $this;
                }
            };
            $file = $aFile->create($mail->id, $this->attachPath, $file);
        }
        return $mail;
    }
    private function initList() : void
    {
        if(!file_exists($this->path) && !@mkdir($this->path))
        {
            $this->error = 'mail dir not found';
            return;
        }
        $mailJsons = scandir($this->path);
        $mailJsons = array_reverse($mailJsons);
        $mails = [];
        foreach($mailJsons as $mailJson)
        {
            if(in_array($mailJson, ['.', '..', 'attach']))
                continue;
            $mailJson = explode('_', $mailJson);
            $mail = new \stdClass();
            $mail->id = $mailJson[0] ?? '';
            $mail->date = substr($mailJson[1], 0, -5) ?? '';
            $mails[] = $mail;
        }
        $this->list = $mails;
    }
    private function sortMailByDate($mail1, $mail2) : int
    {
        return $mail1->date < $mail2->date;
    }
    private function updateBox()
    {
        if(!$this->list)
        {
            $list = array_reverse($this->pop3->list());
            $after = 0;
            $id = 1;
        }
        else if(count($this->list) == count($this->pop3->list())) {return;}
        else
        {
            usort($this->list, [$this, 'sortMailByDate']);
            $list = $this->pop3->list();
            $after = $this->list[0]->date;
            $id = $this->list[0]->id + 1;
        }
        foreach($list as $newMailId)
        {
            $newMail = $this->pop3->get($newMailId);
            $newMailDate = strtotime($newMail->date);
            if($newMailDate <= $after)
                break;
            $listItem = new \stdClass();
            $listItem->id = $id++;
            $listItem->date = $newMailDate;
            $this->list[] = $listItem;
            $this->saveMail($this->getMailName($listItem->id, $listItem->date), $newMail);
            unset($newMail);
        }
        usort($this->list, [$this, 'sortMailByDate']);
    }
    private function getMailName(int $id, int $time) : string
    {
        return $id . '_' . $time . '.json';
    }
    private function saveMail(string $filename, $content) : void
    {
        $attachDir = $this->path . '/attach';
        if(!file_exists($attachDir) && !@mkdir($attachDir))
            $this->error = 'attach dir not found';
        file_put_contents($this->path . '/' . $filename, json_encode($content, 128|256));
    }
    public function error() : string
    {
        return $this->error;
    }
    public function __destruct()
    {
        $this->pop3->close();
    }
}