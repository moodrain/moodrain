<?php
namespace Muyu\Support;

class XML
{
    public static function parse($xml)
    {
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        if (is_object($data) && get_class($data) === 'SimpleXMLElement')
            $data = self::arrarval($data);
        return $data;
    }
    public static function build(
        $data,
        $root = 'xml',
        $item = 'item',
        $attr = '',
        $id = 'id'
    ) {
        if (is_array($attr)) 
        {
            $_attr = array();
            foreach ($attr as $key => $value) 
                $_attr[] = "{$key}=\"{$value}\"";
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml = "<{$root}{$attr}>";
        $xml  .= self::data2Xml($data, $item, $id);
        $xml  .= "</{$root}>";
        return $xml;
    }
    public static function cdata($string)
    {
        return sprintf('<![CDATA[%s]]>', $string);
    }
    private static function arrarval($data)
    {
        if (is_object($data) && get_class($data) === 'SimpleXMLElement')
            $data = (array) $data;
        if (is_array($data))
            foreach ($data as $index => $value)
                $data[$index] = self::arrarval($value);
        return $data;
    }
    private static function data2Xml(Array $data, $item = 'item', $id = 'id')
    {
        $xml = $attr = '';
        foreach ($data as $key => $val) 
        {
            if (is_numeric($key)) 
            {
                $id && $attr = " {$id}=\"{$key}\"";
                $key = $item;
            }
            $xml .= "<{$key}{$attr}>";
            if ((is_array($val) || is_object($val))) 
                $xml .= self::data2Xml((array) $val, $item, $id);
            else 
                $xml .= is_numeric($val) ? $val : self::cdata($val);
            $xml .= "</{$key}>";
        }
        return $xml;
    }
}