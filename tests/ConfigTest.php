<?php

use Muyu\Config;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase {
    function testSetAndGet() {
        $config = new Config([]);
        try{
            $config->get('unExists');
        } catch (Exception $e) {
            $this->assertEquals(7, $e->code(), $e->msg());
        }
        $config->set('key1.key2', 'val');
        $this->assertEquals('val', $config->get('key1.key2'));
        try {
            $config->set('key1.key2', 'newVal');
        } catch (Exception $e) {
            $this->assertEquals(5, $e->code(), $e->msg());
        }
    }
    function testFirstInit() {
        $path = uniqid() . '.json';
        $config = new Config([]);
        Config::setPath($path);
        $config->firstInit([]);
        $this->assertFileExists($path);
        $this->assertEquals([], json_decode(file_get_contents($path), true));
        try {
            $config->firstInit([]);
        } catch (Exception $e) {
            $this->assertEquals(4, $e->code(), $e->msg());
        } finally {
        unlink($path);
        }
    }
    function testReSet() {
        $config = new Config(['key1.key2' => 'val']);
        $config->reset('key1.key2', 'newVal');
        $this->assertEquals('newVal', $config->get('key1.key2'));
    }
    function testTry() {
        $config = new Config([]);
        $this->assertEquals('default', $config->try('key1.key2', 'default'));
    }
    function testModify() {
        $path = uniqid() . '.json';
        $config = new Config([]);
        Config::setPath($path);
        try {
            $config->firstInit(['key1' => ['key2' => 'val']]);
            $config->modify('key1.key2', 'newVal');
            $this->assertEquals(['key1' => ['key2' => 'newVal']], json_decode(file_get_contents($path), true));
        } catch (Exception $e) {}
        finally { unlink($path); }
    }
}