<?php

require __DIR__ . '/MrsAgent/Des.php';
require __DIR__ . '/MrsAgent/Base.php';
require __DIR__ . '/MrsAgent/Client.php';

$key = file_get_contents(__DIR__ . '/encrypt.key');

if (!$key) {
    echo 'key file not exists.' . PHP_EOL;
    exit;
}

$local = $argv[1];
$pathinfo = pathinfo($local);
$remote = '/tmp/' . $pathinfo['basename'];

try {
    $cli = new MrsAgent\Client($key);
    $cli->connect('127.0.0.1', 9527);
    $cli->upload($local, $remote);
} catch (Exception $e) {
    var_dump($e);
}
