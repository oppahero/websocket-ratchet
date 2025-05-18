<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Controllers\SocketController;

require_once __DIR__ . '\vendor\autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SocketController()
        )
    ),
    8080
);

$server->run();