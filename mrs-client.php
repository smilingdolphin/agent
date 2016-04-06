<?php

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_SYNC);

//$client->on('connect', function($cli){
//    $cli->send('hello world'."\n");
//});
//
//$client->on('receive', function($cli, $data = "") {
//    echo 'on receive' . PHP_EOL;
//    if (empty($data)) {
//        $cli->close();
//        echo "closed\n";
//    } else {
//        echo "received: $data \n";
//        sleep(1);
//        $cli->send("hello\n");
//    }
//});
//
//$client->on('close', function($cli){
//    echo "close\n";
//});
//
//$client->on('error', function($cli){
//    echo "error\n";
//});

//$client->onReceive($cli, $data) 

$client->connect('127.0.0.1', 9501, 0.5);
$file = $argv[1];
$client->send('BEGIN' . basename($file) . "\n");
echo $client->recv(8192) . "\n";
$client->sendfile($file);
while ($recv = @$client->recv(8192)) {
    echo $recv . "\n";
}
$client->send('END' . basename($file) . "\n");
echo $client->recv(8192) . "\n";
$client->close();
