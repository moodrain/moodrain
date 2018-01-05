<?php
namespace Muyu;
use PHPMailer\PHPMailer\PHPMailer;

class SMTP
{
    private $host;
    private $port;
    private $user;
    private $pass;
    private $encrypt;

    private $from;
    private $name;
    private $to = [];
    private $replyTo = [];
    private $subject;
    private $html;
    private $text;

    private $mailer;

    public function __construct(Array $config = null)
    {
        if($config)
        {
            foreach($config as $key => $val)
                $this->$key = $val;
        }
        else
        {
            $config = new Config();
            foreach($config('smtp', []) as $key => $val)
                $this->$key = $val;
        }
    }
    public function init(Array $config)
    {
        $this->host = $config['host'] ?? $this->host;
        $this->port = $config['port'] ?? $this->port;
        $this->user = $config['user'] ?? $this->user;
        $this->pass = $config['pass'] ?? $this->pass;
        $this->encrypt = $config['encrypt'] ?? $this->encrypt;
        $this->from = $config['from'] ?? $this->from;
        $this->name = $config['name'] ?? $this->name;
        return $this;
    }
    public function from($mail, $name)
    {
        $this->from = $mail;
        $this->name = $name;
        return $this;
    }
    public function to($mail)
    {
        if(is_array($mail))
            $this->to = $mail;
        else
            $this->to[0] = $mail;
        return $this;
    }
    public function replyTo($mail)
    {
        if(is_array($mail))
            $this->replyTo = $mail;
        else
            $this->replyTo[0] = $mail;
        return $this;
    }
    public function subject($subject)
    {
        $this->subject = $subject;
        return $this;
    }
    public function html($html)
    {
        $this->html = $html;
        return $this;
    }
    public function text($text)
    {
        $this->text = $text;
        return $this;
    }
    public function debug()
    {
        $mail = new PHPMailer();
        $mail->SMTPDebug = 2;
        return $this->send($mail);
    }
    public function send(PHPMailer $mail = null)
    {
        $this->mailer = $mail = $mail ?? new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = $this->host;
        $mail->Username = $this->user;
        $mail->Password = $this->pass;
        $mail->SMTPSecure = $this->encrypt;
        $mail->Port = $this->port;
        $mail->setFrom($this->from, $this->name);
        foreach($this->to as $to)
            $mail->addAddress($to);
        foreach($this->replyTo as $replyTo)
            $mail->addReplyTo($replyTo);
        if($this->html)
            $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $this->subject;
        $mail->Body = $this->html;
        $mail->AltBody = $this->text;
        return $mail->send();
    }
    public function close()
    {
        if($this->mailer)
            $this->mailer->smtpClose();
    }
    public function __destruct()
    {
        $this->close();
    }
}