<?php
namespace Muyu\Support;
class ApiUrl {
    static $urls = [
        'aliDNS' => 'https://alidns.aliyuncs.com',
        'aliSNS' => 'https://dysmsapi.aliyuncs.com',
        'cfDNS' => 'https://api.cloudflare.com/client/v4/zones',
        'wxToken' => 'https://api.weixin.qq.com/cgi-bin/token',
        'wxUserCode' => 'https://open.weixin.qq.com/connect/oauth2/authorize',
        'wxUserToken' => 'https://api.weixin.qq.com/sns/oauth2/access_token',
        'pCloud' => 'https://api.pcloud.com',
    ];
}