<?php

use Muyu\Config;
use Muyu\DNS;
use function Muyu\Support\Fun\conf;
use Muyu\Support\Tool;
use PHPUnit\Framework\TestCase;

final class DNSTest extends TestCase {
    protected function setUp() {
        Config::setPath('config/muyu.json');
        if(!conf('dns', false))
            $this->markTestSkipped('dns config not found');
    }
    function testUpdateRecord() {
        $dns = new DNS();
        $ip = Tool::fake('ip');
        $dns->updateRecord('testmoodraindns', $ip);
        $rs = $dns->getRecord('testmoodraindns');
        $this->assertEquals($ip, $rs->value);
        $dns = new DNS('dns.cloudflare');
        $dns->updateRecord('testmoodraindns', $ip);
        $rs = $dns->getRecord('testmoodraindns');
        $this->assertEquals($ip, $rs->value);
    }
}