<?php

require __DIR__ . '/vendor/autoload.php';

$cacheDir = __DIR__.'/temp';

if (!is_dir($cacheDir)) {
    @mkdir($cacheDir);
}

$loader = new Nette\Loaders\RobotLoader;
$loader->addDirectory(__DIR__.'/php');
$loader->setCacheStorage(new Nette\Caching\Storages\FileStorage($cacheDir));
$loader->register(); // Run the RobotLoader
