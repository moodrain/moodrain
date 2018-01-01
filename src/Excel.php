<?php
namespace Muyu;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Type;

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
    private $error;
    private $firstSheet = true;

    public function __construct($file = null, $sheet = null)
    {
        $this->file = $file;
        $this->sheet = $sheet ?? 'Sheet1';
        $this->ext = $file ? Tool::ext($file) : null;
    }
    public function file($file = null)
    {
        if(!$file)
            return $this->file;
        $this->file = $file;
        return $this;
    }
    public function sheet($sheet = null)
    {
        if(!$sheet)
            return $this->sheet;
        $this->sheet = $sheet;
        return $this;
    }
    public function data(Array $data = null)
    {
        if(!$data)
            return $this->data;
        $this->data = $data;
        return $this;
    }
    public function toArray()
    {
        try
        {
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
        } catch (\Exception $e) {$this->error = $e->getMessage();}
        return false;
    }
    public function next()
    {
        try
        {
            if (!$this->iterator)
            {
                $this->reader = $reader = $this->reader ?? ReaderFactory::create(Type::XLSX);
                $reader->open($this->file);
                $sheets = $reader->getSheetIterator();
                $sheet = null;
                foreach($sheets as $sheetInfo)
                    if($sheetInfo->getName() == $this->sheet)
                        $sheet = $sheetInfo;
                $this->iterator = $sheet->getRowIterator();
                $this->iterator->rewind();
                $row = $this->iterator->current();
            } else
            {
                $this->iterator->next();
                $row = $this->iterator->valid() ? $this->iterator->current() : false;
            }
            return $row;
        } catch (\Exception $e) {$this->error = $e->getMessage();}
        return false;
    }
    public function add($row)
    {
        if(!$this->writer)
        {
            $this->error = 'call add() before toFile() or toBrowser()';
            return false;
        }
        if($this->firstSheet)
        {
            $sheet = $this->writer->getCurrentSheet();
            $sheet->setName($this->sheet);
            $this->firstSheet = false;
        }
        $sheetsInfo = $this->writer->getSheets();
        $sheets = [];
        foreach($sheetsInfo as $sheetInfo)
            $sheets[] = $sheetInfo->getName();
        if(!in_array($this->sheet, $sheets))
        {
            $sheet = $this->writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($this->sheet);
        }
        else
        {
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
        else
            $this->error = 'the row you add is not well format';
    }
    public function to()
    {
        return $this->to;
    }
    public function toFile($file = null)
    {
        try
        {
            $this->file = $file ?? $this->file;
            $this->writer = $this->writer ?? WriterFactory::create(Type::XLSX);
            $this->writer->openToFile($this->file);
            $this->firstSheet = true;
        } catch (\Exception $e) {$this->error = $e->getMessage();}
        return $this;
    }
    public function toBrowser($file = null)
    {
        try
        {
            $this->file = $file ?? $this->file;
            $this->writer = $this->writer ?? WriterFactory::create(Type::XLSX);
            $this->writer->openToBrowser($this->file);
            $this->firstSheet = true;
        } catch (\Exception $e) {$this->error = $e->getMessage();}
        return $this;
    }
    public function download()
    {
        $this->to = 'browser';
        return $this->write();
    }
    public function save()
    {
        $this->to = 'file';
        return $this->write();
    }
    public function close()
    {
        if($this->reader)
            $this->reader->close();
        if($this->writer)
            $this->writer->close();
    }
    public function __destruct()
    {
        $this->close();
    }
    public function error()
    {
        return $this->error;
    }
    private function write()
    {
        try
        {
            $this->writer = $writer = $this->writer ?? WriterFactory::create(Type::XLSX);
            if($this->to == 'file')
                $writer->openToFile($this->file);
            else if($this->to == 'browser')
                $writer->openToBrowser($this->file);
            if(Tool::deep($this->data) == 3)
            {
                foreach($this->data as $sheetName => $sheetData)
                {
                    $sheet = $writer->getCurrentSheet();
                    $sheet->setName($sheetName);
                    $writer->addRows($sheetData);
                }
                return true;
            }
            else if(Tool::deep($this->data) == 2)
            {
                $sheet = $writer->getCurrentSheet();
                $sheet->setName($this->sheet);
                foreach ($this->data as $row)
                    $writer->addRow($row);
                return true;
            }
            else
            {
                $this->error = 'the data to write is not well format';
                return false;
            }
        } catch (\Exception $e) {$this->error = $e->getMessage();}
        return false;
    }
}