<?php

require __DIR__ . '/MrsAgent/Des.php';
require __DIR__ . '/MrsAgent/Base.php';
require __DIR__ . '/MrsAgent/Server.php';

$key = file_get_contents(__DIR__ . '/encrypt.key');

if (!$key) {
    echo 'key file not exists.' . PHP_EOL;
    exit;
}

$svr = new MrsAgent\Server($key);
$svr->run();
