<?php
namespace Muyu\Secondary;
use Muyu\Support\Traits\MuyuExceptionTrait;
use function Muyu\Support\Fun\conf;
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
    private $to;
    private $replyTo;
    private $subject;
    private $html;
    private $text;
    private $mailer;
    private $error = '';

    use MuyuExceptionTrait;
    public function __construct($muyuConfig = 'smtp.default', $init = true) {
        $this->initError();
        if($init)
            $this->init(conf($muyuConfig));
    }
    public function init($config) {
        $this->to = [];
        $this->replyTo = [];
        foreach($config as $key => $val)
            $this->$key = $val;
        $this->pass = base64_decode($config['pass'] ?? '');
        return $this;
    }
    public function from($mail, $name) {
        $this->from = $mail;
        $this->name = $name;
        return $this;
    }
    public function to($mail) {
        if(is_array($mail))
            $this->to = $mail;
        else
            $this->to[0] = $mail;
        return $this;
    }
    public function replyTo($mail) {
        if(is_array($mail))
            $this->replyTo = $mail;
        else
            $this->replyTo[0] = $mail;
        return $this;
    }
    public function subject($subject) {
        $this->subject = $subject;
        return $this;
    }
    public function html($html) {
        $this->html = $html;
        return $this;
    }
    public function text($text) {
        $this->text = $text;
        return $this;
    }
    public function content($content) {
        return $this->html('<p>' . $content . '</p>')->text(strip_tags($content));
    }
    public function debug() {
        $mail = new PHPMailer();
        $mail->SMTPDebug = 2;
        return $this->send($mail);
    }
    public function send(PHPMailer $mail = null) {
        $this->mailer = $mail = $mail ?? new PHPMailer();
        $mail->isSMTP();
        $mail->SMTPAuth = true;
        $mail->Host = $this->host;
        $mail->Username = $this->user;
        $mail->Password = $this->pass;
        $mail->SMTPSecure = $this->encrypt ?? 'ssl';
        $mail->Port = $this->port ?? 465;
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
    public function close() {
        if($this->mailer)
            $this->mailer->smtpClose();
    }
    public function __destruct() {
        $this->close();
    }
}