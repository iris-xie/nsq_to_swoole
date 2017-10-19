<?php

function classLoader($class) {
    $path = str_replace(['\\', 'Iris/NsqToSwoole/'], [DIRECTORY_SEPARATOR, ''], $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}

spl_autoload_register('classLoader');
