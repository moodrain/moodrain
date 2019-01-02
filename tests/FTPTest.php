<?php

use Muyu\Config;
use Muyu\FTP;
use function Muyu\Support\Fun\conf;
use PHPUnit\Framework\TestCase;

final class FTPTest extends TestCase {
    static $tmpFile;
    protected function setUp() {
        Config::setPath(__DIR__ . '/../config/muyu.json');
        if(!conf('ftp', false))
            $this->markTestSkipped('ftp config not found');
        self::$tmpFile = uniqid() . '.tmp';
        file_put_contents(self::$tmpFile, 'tmpFile content');
    }
    protected function tearDown() {
        unlink(self::$tmpFile);
    }
    function testUpload() {
        $tmpFile = self::$tmpFile;
        $ftp = new FTP();
        $ftp->mkdir('moodrainTestDir');
        $ftp->server('moodrainTestDir/tmpFile.tmp')->local($tmpFile)->put();
        $list = $ftp->server('moodrainTestDir')->list();
        $this->assertEquals(['moodrainTestDir/tmpFile.tmp'], $list);
        $ftp->close();
    }
    function testDownload() {
        $tmpFile = self::$tmpFile;
        $ftp = new FTP();
        $ftp->server('moodrainTestDir/tmpFile.tmp')->local($tmpFile)->get();
        $this->assertEquals('tmpFile content', file_get_contents($tmpFile));
        $ftp->close();
    }
    function testDelete() {
        $ftp = new FTP();
        $ftp->server('moodrainTestDir')->enforce()->rmdir();
        $list = $ftp->server('moodrainTestDir')->list();
        $ftp->close();
        $this->assertEquals([], $list);
    }
}