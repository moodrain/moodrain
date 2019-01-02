<?php

use Muyu\Excel;
use PHPUnit\Framework\TestCase;

final class ExcelTest extends TestCase {
    static $tmpFile;
    protected function setUp() {
        self::$tmpFile = uniqid() . '.tmp';
    }
    protected function tearDown() {
        unlink(self::$tmpFile);
    }
    function testWriteAndReadByArray() {
            $tmpFile = self::$tmpFile;
            $excelWrite = new Excel();
            $excelWrite->toFile($tmpFile)->data([
                'Sheet1' => [
                    [1, 2, 3],
                    [4, 5, 6],
                ],
                'Sheet2' => [
                    [7, 8, 9],
                ],
            ])->save();
            $excelWrite->close();
            $excelRead = new Excel();
            $arr1 = $excelRead->file($tmpFile)->sheet('Sheet1')->toArray();
            $arr2 = $excelRead->sheet('Sheet2')->toArray();
            $excelRead->close();
            $this->assertEquals([[1, 2, 3], [4, 5, 6]], $arr1);
            $this->assertEquals([[7, 8, 9]], $arr2);
    }
    function testWriteAndReadByRow() {
        $tmpFile = self::$tmpFile;
        $excelWrite = new Excel();
        $excelWrite->toFile($tmpFile)->sheet('Sheet1')->add([1, 2, 3]);
        $excelWrite->sheet('Sheet2')->add([[4, 5, 6], [7, 8, 9]]);
        $excelWrite->close();
        $excelRead = new Excel();
        $arr1 = $excelRead->file($tmpFile)->sheet('Sheet1')->next();
        $arr2 = $excelRead->sheet('Sheet2')->next();
        $arr3 = $excelRead->next();
        $excelRead->close();
        $this->assertEquals([1, 2, 3], $arr1);
        $this->assertEquals([4, 5, 6], $arr2);
        $this->assertEquals([7, 8, 9], $arr3);
    }
}