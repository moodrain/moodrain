<?php
namespace Muyu\Secondary;

use Muyu\Support\Tool;
use Muyu\Support\Traits\MuyuExceptionTrait;
use function Muyu\Support\Fun\conf;

class POPStore
{
    private $path;
    private $attachPath;
    private $list;
    private $pop3;

    use MuyuExceptionTrait;
    function __construct($muyuConfig = 'pop3.default', $init = true) {
        $this->initError();
        if($init)
            $this->init(conf($muyuConfig));
    }
    function init($config, $getAttach = false) {
        $this->list = [];
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->pop3 = new POP3('pop3.default', false);
        $this->pop3->init($config, $getAttach);
        $this->pop3->initBox(true);
        $this->attachPath = $this->pop3->attachPath();
        $this->initList();
        $this->updateBox();
        return $this;
    }
    function list() {
        return $this->list;
    }
    function mailsInPage($offset, $limit) {
        $mails = $this->list;
        foreach($mails as & $mail)
            $mail->content = $this->getMail($mail->id);
        return array_slice($mails, $offset, $limit);
    }
    function mailsInDate(string $begin, string $end) {
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
    function path() {
        return $this->path;
    }
    function attachPath() {
        return $this->attachPath;
    }
    function getMail($id) {
        $mail = null;
        foreach($this->list as $aMail)
            if($aMail->id == $id) {
                $mail = $aMail;
                break;
            }
        if(!$mail)
            return null;
        $mail = json_decode(file_get_contents($this->path . '/' . $mail->id . '_' . $mail->date . '.json'));
        foreach($mail->file as & $file)  {
            $aFile = new class {
                public $name;
                public $ext;
                private $path;
                function path() {
                    return $this->path;
                }
                function create($id, $attachPath, $file) {
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
    private function initList() {
        if(!file_exists($this->path)) {
            Tool::mkdir($this->path);
            if(!file_exists($this->path)) {
                $this->addError(1, 'mail dir not found');
                return;
            }
        }
        $mailJsons = scandir($this->path);
        $mailJsons = array_reverse($mailJsons);
        $mails = [];
        foreach($mailJsons as $mailJson) {
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
    private function sortMailByDate($mail1, $mail2) {
        return $mail1->date < $mail2->date;
    }
    private function updateBox() {
        if(!$this->list) {
            $list = array_reverse($this->pop3->list());
            $after = 0;
            $id = 1;
        }
        else if(count($this->list) == count($this->pop3->list()))
            return;
        else {
            usort($this->list, [$this, 'sortMailByDate']);
            $list = $this->pop3->list();
            $after = $this->list[0]->date;
            $id = $this->list[0]->id + 1;
        }
        foreach($list as $newMailId) {
            $newMail = $this->pop3->get($newMailId);
            $newMailDate = strtotime($newMail->date);
            if($newMailDate <= $after) {
                if(isset($newMail->file)) {
                    foreach($newMail->file as $file) {
                        @unlink($file);
                        if(count(scandir(dirname($file))) == 2)
                            @rmdir(dirname($file));
                    }
                }
                break;
            }
            $listItem = new \stdClass();
            $listItem->id = $id++;
            $listItem->date = $newMailDate;
            $this->list[] = $listItem;
            $this->saveMail($this->getMailName($listItem->id, $listItem->date), $newMail);
            unset($newMail);
        }
        usort($this->list, [$this, 'sortMailByDate']);
    }
    private function getMailName($id, $time) {
        return $id . '_' . $time . '.json';
    }
    private function saveMail($filename, $content) {
        $attachDir = $this->path . '/attach';
        if(!file_exists($attachDir) && !@mkdir($attachDir))
            $this->addError(2, 'attach dir not found');
        file_put_contents($this->path . '/' . $filename, json_encode($content, 128|256));
    }
    function close() {
        if($this->pop3)
            $this->pop3->close();
    }
    function __destruct() {
        $this->close();
    }
}