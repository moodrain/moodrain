<?php

use Muyu\Support\Base\Arr;

function array_add($array, $key, $value) {
    return Arr::add($array, $key, $value);
}

function array_collapse($array) {
    return Arr::collapse($array);
}

function array_divide($array) {
    return Arr::divide($array);
}

function array_dot($array, $prepend = '') {
    return Arr::dot($array, $prepend);
}

function array_except($array, $keys) {
    return Arr::except($array, $keys);
}

function array_first($array, callable $callback = null, $default = null) {
    return Arr::first($array, $callback, $default);
}

function array_flatten($array, $depth = INF) {
    return Arr::flatten($array, $depth);
}

function array_forget(&$array, $keys) {
    Arr::forget($array, $keys);
}

function array_get($array, $key, $default = null) {
    return Arr::get($array, $key, $default);
}

function array_has($array, $keys) {
    return Arr::has($array, $keys);
}

function array_last($array, callable $callback = null, $default = null) {
    return Arr::last($array, $callback, $default);
}

function array_only($array, $keys) {
    return Arr::only($array, $keys);
}

function array_pluck($array, $value, $key = null) {
    return Arr::pluck($array, $value, $key);
}

function array_prepend($array, $value, $key = null) {
    return Arr::prepend($array, $value, $key);
}

function array_pull(&$array, $key, $default = null) {
    return Arr::pull($array, $key, $default);
}

function array_random($array, $num = null) {
    return Arr::random($array, $num);
}

function array_set(&$array, $key, $value) {
    return Arr::set($array, $key, $value);
}

function array_sort($array, $callback = null) {
    return Arr::sort($array, $callback);
}

function array_sort_recursive($array) {
    return Arr::sortRecursive($array);
}

function array_where($array, callable $callback) {
    return Arr::where($array, $callback);
}

function array_wrap($value) {
    return Arr::wrap($value);
}