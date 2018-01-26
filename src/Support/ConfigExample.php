<?php
namespace Muyu\Support;

class ConfigExample
{
    public function __construct()
    {
        $oss = $this->oss = new \stdClass();
        $ossDef = $oss->default = new \stdClass();
        $ossDef->accessKeyId = '';
        $ossDef->accessKeySecret = '';
        $ossDef->address = '';
        $ossDef->domain = '';
        $ossDef->bucketName = '';
        $ossDef->endPoint = '';
        $ossDef->policy = '';
        $ossDef->authPolicy = '';
        $ossDef->expire = 0;
        $ossDef->cors = '*';

        $log = $this->log = new \stdClass();
        $logDef = $log->default = new \stdClass();
        $logDef->file = './storage/log.json';

        $ftp = $this->ftp = new \stdClass();
        $ftpDef = $ftp->default = new \stdClass();
        $ftpDef->host = '';
        $ftpDef->port = 21;
        $ftpDef->user = '';
        $ftpDef->pass = '';
        $ftpDef->prefix = '';
        $ftpDef->ssl = false;

        $sms = $this->sms = new \stdClass();
        $captcha = $sms->captcha = new \stdClass();
        $captcha->accessKeyId = '';
        $captcha->accessKeySecret = '';
        $captcha->signName = '';
        $captcha->templateCode = '';

        $wechat = $this->wechat = new \stdClass();
        $wechatDef = $wechat->default = new \stdClass();
        $wechatDef->appId = '';
        $wechatDef->appSecret = '';
        $wechatDef->token = '';
        $wechatDef->encodingAESKey = '';
        $wechatDef->host = '';
        $wechatDef->getUserAccessTokenUrl = '';

        $smtp = $this->smtp = new \stdClass();
        $smtpDef = $smtp->default = new \stdClass();
        $smtpDef->host = '';
        $smtpDef->port = 465;
        $smtpDef->user = '';
        $smtpDef->pass = '';
        $smtpDef->from = '';
        $smtpDef->name = '';
        $smtpDef->replyTo = [];

        $pop3 = $this->pop3 = new \stdClass();
        $pop3Def = $pop3->default = new \stdClass();
        $pop3Def->host = '';
        $pop3Def->port = 995;
        $pop3Def->user = '';
        $pop3Def->pass = '';
        $pop3Def->path = './storage/mails';

        $database = $this->database = new \stdClass();
        $databaseDef = $database->default = new \stdClass();
        $databaseDef->host = 'localhost';
        $databaseDef->type = 'mysql';
        $databaseDef->user = 'root';
        $databaseDef->pass = '';

        $command = $this->command = new \stdClass();
        $command->host = 'https://moodrain.cn';
        $command->user = '';
        $command->pass = '';
    }
    public function json() : string
    {
        return json_encode($this, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    public function save() : void
    {
        file_put_contents('muyu.json', $this->json());
    }
}