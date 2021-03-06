<?php

use Muyu\Support\Base\Arr;
use Muyu\Config;
use Muyu\Curl;

require_once __DIR__ . '/arrHelper.php';
require_once __DIR__ . '/strHelper.php';
require_once __DIR__ . '/dbHelper.php';

function conf(...$e) {
    static $conf = null;
    $conf = $conf ? $conf : new Config;
    if(count($e) == 1) {
        return $conf($e[0]);
    } else {
        return $conf($e[0], $e[1]);
    }
}

function app_loader() {
    spl_autoload_register(function($className) {
        $prefix = 'App';
        $nameSpaces = explode('\\', $className);
        if(array_shift($nameSpaces) != $prefix) {
            return;
        }
        $baseName = array_pop($nameSpaces);
        $path = 'app/' . implode('/', $nameSpaces) . '/' . $baseName . '.php';
        if(! file_exists(ROOT . $path)) {
            throw new \Exception('class not found: ' . $className);
        }
        require ROOT . $path;
    });
}

function now($unix = false) {
    return $unix ? time() : date('Y-m-d H:i:s');
}

function rs($p1 = null, $p2 = null, $p3 = null) {
    $code = 0;
    $msg = '';
    $data = null;
    if(is_array($p1) || is_object($p1) || is_null($p1)) {
        $data = $p1;
    }
    if(is_string($p1)) {
        $msg = $p1;
    } else if(is_string($p2)) {
        $msg = $p2;
    }
    if(is_int($p1)) {
        $code = $p1;
    } else if(is_int($p2)) {
        $code = $p2;
    } else if(is_int($p3)) {
        $code = $p3;
    }
    return compact('code', 'msg', 'data');
}

function ers($msg = '', $code = 1, $data = null) {
    return rs($data, $msg, $code);
}

function mvc_render($raw) {
    header('Content-Type: application/json');
    echo json_encode($raw, JSON_UNESCAPED_UNICODE);
}

function mvc_get_controller_action() {
    if(! empty($_REQUEST['c'])) {
        return [$_REQUEST['c'], $_REQUEST['a'] ?? 'index'];
    } else if(! empty($_REQUEST['a'])) {
        $action = explode('/', $_REQUEST['a']);
        count($action) == 1 && array_unshift($action, 'index');
        return $action;
    } else {
        return ['index', 'index'];
    }
}

function mvc_do_action($controller, $action) {
    $controllerName = 'App\\Controller\\' . ucfirst($controller) . 'Controller';
    if(! class_exists($controllerName)) {
        return ers('controller not found: ' . $controller);
    }
    $controller = new $controllerName;
    if(! method_exists($controllerName, $action)) {
        return ers('action not found: ' . $action);
    }
    return call_user_func([$controller, $action], Arr::except($_REQUEST, ['a', 'c']));
}

function mvc_on() {
    $rs = mvc_do_action(...mvc_get_controller_action());
    is_object($rs) && ! (property_exists('code', $rs) && property_exists('msg', $rs) && property_exists('data', $rs)) && $rs = rs($rs);
    is_array($rs) && ! (array_key_exists('code', $rs) && array_key_exists('msg', $rs) && array_key_exists('data', $rs)) && $rs = rs($rs);
    mvc_render($rs);
}


function curl($url) {
    $curl = new Curl($url);
    $curl->get();
    var_dump($curl->responseHeader());
    var_dump( $curl->content());
}

function dd(...$args) {
    $toDump = count($args) === 1 ? $args[0] : $args;
    var_dump($toDump);
    exit;
}

function dj(...$args) {
    $toJson = count($args) === 1 ? $args[0] : $args;
    header('Content-Type: application/json');
    echo json_encode($toJson, 128|256);
    exit;
}

function retry($times, callable $callback, $sleep = 0, callable $when = null) {
    $attempts = 0;
    beginning:
    $attempts++;
    $times--;
    try {
        return $callback($attempts);
    } catch (Exception $e) {
        if ($times < 1 || ($when && ! $when($e))) {
            throw $e;
        }
        $sleep && usleep($sleep * 1000);
        goto beginning;
    }
}

function value($value) {
    return $value instanceof Closure ? $value() : $value;
}

function data_get($target, $key, $default = null) {
    if (is_null($key)) {
        return $target;
    }
    $key = is_array($key) ? $key : explode('.', $key);
    foreach ($key as $i => $segment) {
        unset($key[$i]);
        if (is_null($segment)) {
            return $target;
        }
        if ($segment === '*') {
            if (! is_array($target)) {
                return value($default);
            }
            $result = [];
            foreach ($target as $item) {
                $result[] = data_get($item, $key);
            }
            return in_array('*', $key) ? Arr::collapse($result) : $result;
        }
        if (Arr::accessible($target) && Arr::exists($target, $segment)) {
            $target = $target[$segment];
        } elseif (is_object($target) && isset($target->{$segment})) {
            $target = $target->{$segment};
        } else {
            return value($default);
        }
    }
    return $target;
}

function data_fill(&$target, $key, $value) {
    return data_set($target, $key, $value, false);
}

function data_set(&$target, $key, $value, $overwrite = true)
{
    $segments = is_array($key) ? $key : explode('.', $key);
    if (($segment = array_shift($segments)) === '*') {
        if (! Arr::accessible($target)) {
            $target = [];
        }
        if ($segments) {
            foreach ($target as &$inner) {
                data_set($inner, $segments, $value, $overwrite);
            }
        } elseif ($overwrite) {
            foreach ($target as &$inner) {
                $inner = $value;
            }
        }
    } elseif (Arr::accessible($target)) {
        if ($segments) {
            if (! Arr::exists($target, $segment)) {
                $target[$segment] = [];
            }
            data_set($target[$segment], $segments, $value, $overwrite);
        } elseif ($overwrite || ! Arr::exists($target, $segment)) {
            $target[$segment] = $value;
        }
    } elseif (is_object($target)) {
        if ($segments) {
            if (! isset($target->{$segment})) {
                $target->{$segment} = [];
            }
            data_set($target->{$segment}, $segments, $value, $overwrite);
        } elseif ($overwrite || ! isset($target->{$segment})) {
            $target->{$segment} = $value;
        }
    } else {
        $target = [];
        if ($segments) {
            data_set($target[$segment], $segments, $value, $overwrite);
        } elseif ($overwrite) {
            $target[$segment] = $value;
        }
    }
    return $target;
}

function blank($value) {
    if (is_null($value)) {
        return true;
    }
    if (is_string($value)) {
        return trim($value) === '';
    }
    if (is_numeric($value) || is_bool($value)) {
        return false;
    }
    if ($value instanceof Countable) {
        return count($value) === 0;
    }
    return empty($value);
}

function filled($value) {
    return ! blank($value);
}

function head($array) {
    return reset($array);
}

function last($array) {
    return end($array);
}

function object_get($object, $key, $default = null) {
    if (is_null($key) || trim($key) == '') {
        return $object;
    }
    foreach (explode('.', $key) as $segment) {
        if (! is_object($object) || ! isset($object->{$segment})) {
            return value($default);
        }
        $object = $object->{$segment};
    }
    return $object;
}

function preg_replace_array($pattern, array $replacements, $subject) {
    return preg_replace_callback($pattern, function () use (&$replacements) {
        foreach ($replacements as $key => $value) {
            return array_shift($replacements);
        }
    }, $subject);
}

function throw_if($condition, $exception, ...$parameters) {
    if ($condition) {
        if(is_string($exception)) {
            if(class_exists($exception)) {
                throw new $exception(...$parameters);
            }
            throw new \Exception(...$parameters);
        }
        throw $exception;
    }
    return $condition;
}

function throw_unless($condition, $exception, ...$parameters) {
    if (! $condition) {
        if(is_string($exception)) {
            if(class_exists($exception)) {
                throw new $exception(...$parameters);
            }
            throw new \Exception(...$parameters);
        }
        throw $exception;
    }
    return $condition;
}

function transform($value, callable $callback, $default = null) {
    if (filled($value)) {
        return $callback($value);
    }
    if (is_callable($default)) {
        return $default($value);
    }
    return $default;
}

function with($value, callable $callback = null) {
    return is_null($callback) ? $value : $callback($value);
}