<?php

require __DIR__ . '/../vendor/autoload.php';

$loop = \React\EventLoop\Factory::create();
$socket = new \React\Socket\Server($loop);
$socket->listen(8080, '0.0.0.0');

$chat_server = new ChatServer($loop);

$component = new \Ratchet\Http\HttpServer( 
    new \Ratchet\WebSocket\WsServer($chat_server) 
);

$server = new \Ratchet\Server\IoServer($component, $socket, $loop);

$server->run();
