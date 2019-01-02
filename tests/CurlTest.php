<?php

use Muyu\Curl;
use PHPUnit\Framework\TestCase;

final class CurlTest extends TestCase{
    static $url = 'https://moodrain.cn/api/http';
    function testRedirect() {
        $curl = new Curl(str_replace('https://', '', self::$url));
        $rs = $curl->data(['key' => 'val'])->post();
        $this->assertEquals(['key' => 'val'], $rs['content']);
    }
    function testGet() {
        $curl = new Curl(str_replace('/http', '',self::$url));
        $rs = $curl->header(['headerKey' => 'headerVal'])->cookie(['cookieKey' => 'cookieVal'])->query(['queryKey' => 'queryVal'])->path('http')->get();
        $this->assertEquals(['queryKey' => 'queryVal'], $rs['query']);
        $this->assertEquals(['cookieKey' => 'cookieVal'], $rs['cookie']);
        $this->assertEquals('headerVal', $rs['header']['headerkey'][0] ?? null);
    }
    function testPost() {
        $tmpFile1 = uniqid() . '1.tmp';
        $tmpFile2 = uniqid() . '2.tmp';
        file_put_contents($tmpFile1, 'tmpFile1 content');
        file_put_contents($tmpFile2, 'tmpFile2 content');
        try {
            $curl = new Curl(self::$url);
            $rs = $curl->data(['id' => 1, 'name' => 'muyu'])->file(['file1' => $tmpFile1, 'file2' => $tmpFile2])->post();
        } catch (Exception $e) {}
        finally {
            unlink($tmpFile1);
            unlink($tmpFile2);
            $this->assertEquals(['id' => 1, 'name' => 'muyu'], $rs['content']);
            $this->assertEquals(['file1' => [], 'file2' => []], $rs['file']);
        }
    }
    function testPut() {
        $tmpFile = uniqid() . '.tmp';
        file_put_contents($tmpFile, 'tmpFile content');
        try {
            $curl = new Curl(self::$url);
            $rs = $curl->file($tmpFile)->put();
            $curl->close();
        } catch (Exception $e) {}
        finally { unlink($tmpFile); }
        $this->assertEquals('tmpFile content', $rs['raw']);
    }
    function testPatch() {
        $curl = new Curl(self::$url);
        $rs = $curl->json(['id' => 2])->patch();
        $this->assertEquals('PATCH', $rs['method']);
        $this->assertEquals(['id' => 2], $rs['content']);
    }
    function testDelete() {
        $curl = new Curl(self::$url);
        $rs = $curl->data(['id' => 3])->delete();
        $this->assertEquals('DELETE', $rs['method']);
        $this->assertEquals(['id' => 3], $rs['content']);
    }
}