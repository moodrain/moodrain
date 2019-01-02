<?php

use Muyu\Config;
use Muyu\OSS;
use function Muyu\Support\Fun\conf;
use PHPUnit\Framework\TestCase;

final class OSSTest extends TestCase {
    static $tmpFile;
    static function setUpBeforeClass() {
        Config::setPath(__DIR__ . '/../config/muyu.json');
        self::$tmpFile = uniqid() . '.tmp';
    }
    protected function setUp() {
        if(!conf('oss', false))
            $this->markTestSkipped('oss config not found');
        file_put_contents(self::$tmpFile, 'tmpFile content');
    }
    protected function tearDown() {
        unlink(self::$tmpFile);
    }

    function testUpload() {
        $tmpFile = self::$tmpFile;
        $oss = new OSS();
        $oss->prefix('moodrainTestDir')->put($tmpFile, $tmpFile);
        $list = $oss->prefix('moodrainTestDir')->list();
        $this->assertEquals('moodrainTestDir/' . $tmpFile, $list[0]['Key']);
    }
    function testDownload() {
        $tmpFile = self::$tmpFile;
        $oss = new OSS();
        $content = $oss->get('moodrainTestDir/' . $tmpFile);
        $this->assertEquals('tmpFile content', $content);
    }
    function testDelete() {
        $tmpFile = self::$tmpFile;
        $oss = new OSS();
        $oss->del('moodrainTestDir/' . $tmpFile);
        $list = $oss->list('moodrainTestDir/');
        $this->assertEquals([], $list);
    }
}