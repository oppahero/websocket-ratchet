<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\Controllers\SocketController;

require_once __DIR__ . '\vendor\autoload.php';

$PORT = 8080;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new SocketController()
        )
    ),
    $PORT
);

echo "Server is running! (port:{$PORT})\n";

$server->run();