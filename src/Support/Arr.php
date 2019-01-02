<?php
namespace Muyu\Support;

use Muyu\Support\Traits\MuyuExceptionTrait;

class Arr
{
    private $arr;

    use MuyuExceptionTrait;
    function __construct($arr = []) {
        $this->initError();
        $this->arr = $arr;
    }
    function __get($key) {
        if(!isset($this->arr[$key]))
            $this->addError(1, 'try to get a key not set');
        return $this->arr[$key] ?? null;
    }
    function __set($key, $val) {
        $this->arr[$key] = $val;
    }
    function get($key) {
        if(!isset($this->arr[$key]))
            $this->addError(1, 'try to get a key not set');
        return $this->arr[$key] ?? null;
    }
    function set($key, $val) {
        $this->arr[$key] = $val;
        return $this;
    }
    function del($key, $returnElem = false) {
        if(!isset($this->arr[$key]))
            $this->addError(1, 'try to get a key not set');
        $elem = $this->arr[$key] ?? null;
        unset($this->arr[$key]);
        return $returnElem ? $elem : $this;
    }
    function __unset($key) {
        unset($this->arr[$key]);
    }
    function count() {
        return count($this->arr);
    }
    function arr() {
        return $this->arr;
    }
    function slice($offset, $limit) {
        $this->arr = array_slice($this->arr, $offset, $limit);
        return $this;
    }
    function reverse() {
        $this->arr = array_reverse($this->arr);
        return $this;
    }
    function merge($arr) {
        $this->arr = array_merge($this->arr, $arr);
        return $this;
    }
    function unshift($elem) {
        array_unshift($this->arr, $elem);
        return $this;
    }
    function push($elem) {
        array_push($this->arr, $elem);
        return $this;
    }
    function shift($returnElem = true) {
        $elem = array_shift($this->arr);
        return $returnElem ? $elem : $this;
    }
    function pop($returnElem = true) {
        $elem = array_pop($this->arr);
        return $returnElem ? $elem : $this;
    }
    function foreach(callable $function) {
        array_walk($this->arr, $function);
        return $this;
    }
}