<?php

use Muyu\Support\Base\Str;

function camel_case($value) {
    return Str::camel($value);
}

function ends_with($haystack, $needles) {
    return Str::endsWith($haystack, $needles);
}

function kebab_case($value) {
    return Str::kebab($value);
}

function snake_case($value, $delimiter = '_') {
    return Str::snake($value, $delimiter);
}

function starts_with($haystack, $needles) {
    return Str::startsWith($haystack, $needles);
}

function str_after($subject, $search) {
    return Str::after($subject, $search);
}

function str_before($subject, $search) {
    return Str::before($subject, $search);
}

function str_contains($haystack, $needles) {
    return Str::contains($haystack, $needles);
}

function str_finish($value, $cap) {
    return Str::finish($value, $cap);
}

function str_is($pattern, $value) {
    return Str::is($pattern, $value);
}

function str_limit($value, $limit = 100, $end = '...') {
    return Str::limit($value, $limit, $end);
}

function str_random($length = 16) {
    return Str::random($length);
}

function str_replace_array($search, array $replace, $subject) {
    return Str::replaceArray($search, $replace, $subject);
}

function str_replace_first($search, $replace, $subject) {
    return Str::replaceFirst($search, $replace, $subject);
}

function str_replace_last($search, $replace, $subject) {
    return Str::replaceLast($search, $replace, $subject);
}

function str_slug($title, $separator = '-') {
    return Str::slug($title, $separator);
}

function str_start($value, $prefix) {
    return Str::start($value, $prefix);
}

function studly_case($value) {
    return Str::studly($value);
}

function title_case($value) {
    return Str::title($value);
}
