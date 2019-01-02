<?php
namespace Muyu;

use Muyu\Support\Traits\MuyuExceptionTrait;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;
use Muyu\Support\Tool;

class Excel
{
    private $file;
    private $sheet;
    private $ext;
    private $reader;
    private $writer;
    private $to;
    private $iterator;
    private $data;
    private $firstSheet;

    use MuyuExceptionTrait;
    function __construct($file = null, $sheet = null) {
        $this->initError();
        $this->firstSheet = true;
        $this->file = $file;
        $this->sheet = $sheet ?? 'Sheet1';
        $this->ext = $file ? Tool::ext($file) : null;
        $this->iterator = [];
    }
    function file($file = null) {
        if(!$file)
            return $this->file;
        $this->file = $file;
        return $this;
    }
    function sheet($sheet = null) {
        if(!$sheet)
            return $this->sheet;
        $this->sheet = $sheet;
        return $this;
    }
    function data($data = null) {
        if(!$data)
            return $this->data;
        $this->data = $data;
        return $this;
    }
    function to() {
        return $this->to;
    }
    function toArray() {
        try {
            $this->reader = $reader = $this->reader ?? ReaderFactory::create(Type::XLSX);
            $reader->open($this->file);
            $sheets = $reader->getSheetIterator();
            $data = [];
            if($this->sheet)
                foreach($sheets as $sheet)
                    if($sheet->getName() == $this->sheet)
                        foreach($sheet->getRowIterator() as $row)
                            $data[] = $row;
            if(!$this->sheet)
                foreach($sheets as $sheet)
                    foreach($sheet->getRowIterator() as $row)
                        $data[] = $row;
            return $data;
        } catch (\Exception $e) {
            $this->addError(1, 'read error', null, $e->getMessage());
        }
        return false;
    }
    function next() {
        try {
            if(!$this->reader) {
                $this->reader = $this->reader ?? ReaderFactory::create(Type::XLSX);
                $this->reader->open($this->file);
            }
            if (!isset($this->iterator[$this->sheet])) {
                $sheets = $this->reader->getSheetIterator();
                $sheet = null;
                foreach($sheets as $sheetInfo)
                    if($sheetInfo->getName() == $this->sheet)
                        $sheet = $sheetInfo;
                $this->iterator[$this->sheet] = $sheet->getRowIterator();
                $this->iterator[$this->sheet]->rewind();
                $row = $this->iterator[$this->sheet]->current();
            } else {
                $this->iterator[$this->sheet]->next();
                $row = $this->iterator[$this->sheet]->valid() ? $this->iterator[$this->sheet]->current() : false;
            }
            return $row;
        } catch (\Exception $e) {
            $this->addError(1, 'read error', null, $e->getMessage());
        }
        return false;
    }
    function toFile($file = null) {
        try {
            $this->file = $file ?? $this->file;
            $this->writer = $this->writer ?? WriterFactory::create(Type::XLSX);
            $this->writer->openToFile($this->file);
            $this->firstSheet = true;
        } catch (\Exception $e) {
            $this->addError(5, 'write error', null, $e->getMessage());
            return false;
        }
        return $this;
    }
    function toBrowser($file = null) {
        try {
            $this->file = $file ?? $this->file;
            $this->writer = $this->writer ?? WriterFactory::create(Type::XLSX);
            $this->writer->openToBrowser($this->file);
            $this->firstSheet = true;
        } catch (\Exception $e) {
            $this->addError(5, 'write error', null, $e->getMessage());
            return false;
        }
        return $this;
    }
    function add($row) {
        if(!$this->writer) {
            $this->addError(2, 'call add() before toFile() or toBrowser()');
            return false;
        }
        if($this->firstSheet) {
            $sheet = $this->writer->getCurrentSheet();
            $sheet->setName($this->sheet);
            $this->firstSheet = false;
        }
        $sheetsInfo = $this->writer->getSheets();
        $sheets = [];
        foreach($sheetsInfo as $sheetInfo)
            $sheets[] = $sheetInfo->getName();
        if(!in_array($this->sheet, $sheets)) {
            $sheet = $this->writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($this->sheet);
        }
        else {
            $sheet = $this->writer->getCurrentSheet();
            foreach ($sheetsInfo as $sheetInfo)
                if($sheetInfo->getName() == $this->sheet)
                    $sheet = $sheetInfo;
            $this->writer->setCurrentSheet($sheet);
        }
        if(Tool::deep($row) == 2)
            $this->writer->addRows($row);
        else if(Tool::deep($row) == 1)
            $this->writer->addRow($row);
        else {
            $this->addError(3, 'the row you add is not well format');
            return false;
        }
    }
    function download() {
        $this->to = 'browser';
        return $this->write();
    }
    function save() {
        $this->to = 'file';
        return $this->write();
    }
    function close() {
        if($this->reader)
            $this->reader->close();
        if($this->writer)
            $this->writer->close();
    }
    function __destruct() {
        $this->close();
    }
    private function write() {
        try {
            $this->writer = $writer = $this->writer ?? WriterFactory::create(Type::XLSX);
            if($this->to == 'file')
                $writer->openToFile($this->file);
            else if($this->to == 'browser')
                $writer->openToBrowser($this->file);
            if(Tool::deep($this->data) == 3) {
                foreach($this->data as $sheetName => $sheetData) {
                    if($this->firstSheet) {
                        $sheet = $writer->getCurrentSheet();
                        $this->firstSheet = false;
                    }
                    else
                        $sheet = $writer->addNewSheetAndMakeItCurrent();
                    $sheet->setName($sheetName);
                    $writer->addRows($sheetData);
                }
                return true;
            }
            else if(Tool::deep($this->data) == 2) {
                $sheet = $writer->getCurrentSheet();
                $sheet->setName($this->sheet);
                foreach ($this->data as $row)
                    $writer->addRow($row);
                return true;
            }
            else {
                $this->addError(4, 'the data to write is not well format');
                return false;
            }
        } catch (\Exception $e) {
            $this->addError(1, 'read error', null, $e->getMessage());
        }
        return false;
    }
}