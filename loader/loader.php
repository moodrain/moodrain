<?php

$dir = __DIR__ . '/../';

$helpers = [
    'src/Support/Helpers/helper.php',
];

foreach($helpers as $helper) {
    $require($helper);
}

spl_autoload_register(function($className) use ($dir) {
    $prefix = 'Muyu';
    $nameSpaces = explode('\\', $className);
    if(array_shift($nameSpaces) != $prefix) {
        return;
    }
    $baseName = array_pop($nameSpaces);
    $path = 'src/' . (empty($nameSpaces) ? '' : implode('/', $nameSpaces) . '/') . $baseName . '.php';
    if(! file_exists($dir . $path)) {
        throw new \Exception('class not found: ' . $className);
    }
    require $dir . $path;
});
