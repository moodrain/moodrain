<?php
namespace Muyu\Support\DNS;

class Record {
    private $id;

    private $type;
    private $rr;
    private $value;
    private $ttl;
    function __construct($id, $type, $rr, $value, $ttl) {
        $this->id = $id;
        $this->type = $type;
        $this->rr = $rr;
        $this->value = $value;
        $this->ttl = $ttl;
    }
    function __get($name) {
        return $this->$name;
    }
    function id() {
        return $this->id;
    }
    function type() {
        return $this->type;
    }
    function rr() {
        return $this->rr;
    }
    function value() {
        return $this->value;
    }
    function ttl() {
        return $this->ttl;
    }

}
