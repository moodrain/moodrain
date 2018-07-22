<?php
namespace Muyu\Support;

class Arr
{
    private $arr;

    function __construct(array $arr = []) {
        $this->arr = $arr;
    }
    function __get($key) {
        return $this->arr[$key] ?? null;
    }
    function __set($key, $val) {
        $this->arr[$key] = $val;
    }
    function get($key) {
        return $this->arr[$key] :: null;
    }
    function set($key, $val) : Arr {
        $this->arr[$key] = $val;
        return $this;
    }
    function del($key, bool $returnElem = false) {
        $elem = $this->arr[$key];
        unset($this->arr[$key]);
        return $returnElem ? $elem : $this;
    }
    function __unset($key) {
        unset($this->arr[$key]);
    }
    function count() : int {
        return count($this->arr);
    }
    function arr() : array {
        return $this->arr;
    }
    function slice(int $offset, int $limit) : Arr {
        $this->arr = array_slice($this->arr, $offset, $limit);
        return $this;
    }
    function reverse() : Arr {
        $this->arr = array_reverse($this->arr);
        return $this;
    }
    function merge(array $arr) : Arr {
        $this->arr = array_merge($this->arr, $arr);
        return $this;
    }
    function unshift($elem) : Arr {
        array_unshift($this->arr, $elem);
        return $this;
    }
    function push($elem) : Arr {
        array_push($this->arr, $elem);
        return $this;
    }
    function shift(bool $returnElem = false) {
        $elem = array_shift($this->arr);
        return $returnElem ? $elem : $this;
    }
    function pop(bool $returnElem = false) {
        $elem = array_pop($this->arr);
        return $returnElem ? $elem : $this;
    }
    function foreach(callable $function) : Arr {
        array_walk($this->arr, $function);
        return $this;
    }
}