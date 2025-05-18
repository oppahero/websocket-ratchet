<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

// $app->get('/', function (Request $request, Response $response, array $args) {
//     try {
//         return json_encode(array("status" => 'Client PHP Activo'));
//     } catch (Exception $e) {
//         $this->logger->info("Exception: ", $e->getMessage());
//         return json_encode(array("status" => false));
//     }
// });

$app->post('/', function (Request $request, Response $response, array $args) {
    try {

        // decode data from the caller
        $GLOBALS['body'] = json_decode($request->getBody());

        /**
         * Send to Socket
         */
        \Ratchet\Client\connect('ws://127.0.0.1:8080')->then(function ($conn) {
            $conn->on('message', function ($msg) use ($conn) {
                echo "Received: {$msg}\n";
            });

            $conn->send(
                json_encode(array(
                    "user" => $GLOBALS['body']->user,
                    "group" => isset($GLOBALS['body']->group) ? $GLOBALS['body']->group : '',
                    "message" => $GLOBALS['body']->message,
                ))
            );

            $conn->close();

        }, function ($e) {
            echo "Could not connect: {$e->getMessage()}\n";
            $this->logger->info("Can not connect: {$e->getMessage()}");
        });

        return json_encode(array("status" => true));

    } catch (Exception $e) {
        $this->logger->info("Exception: ", $e->getMessage());
        return json_encode(array("status" => false));
    }
});

