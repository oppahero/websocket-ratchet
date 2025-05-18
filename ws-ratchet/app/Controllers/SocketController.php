<?php
namespace App\Controllers;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class SocketController implements MessageComponentInterface
{
    protected $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    /**
     * 
     */
    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $client = new \stdClass();
        $client->conn = $conn;
        $client->user = '';
        $client->group = '';
        $client->message = '';
        $this->clients->attach($client);
        echo "New connection! ({$conn->resourceId})\n";
    }

    /**
     * 
     */
    public function onMessage(ConnectionInterface $from, $msg)
    {
        // number of clients except the sender
        $numRecv = count($this->clients) - 1;

        // convert message
        $message = json_decode($msg);

        // save information of the sender
        foreach ($this->clients as $client) {
            if ($from === $client->conn) {
                $client->user = $message->user;
                $client->group = isset($message->group) ? $message->group : '';
                $client->message = $message->message;
            }
        }

        echo sprintf(
            'Connection %d sending message "%s" to %d other connection%s' . "\n"
            ,
            $from->resourceId,
            $msg,
            $numRecv,
            $numRecv == 1 ? '' : 's'
        );

        foreach ($this->clients as $client) {
            if ($from !== $client->conn) {
                // The sender is not the receiver, send to each user connected
                $client->conn->send($msg);
            }
        }
    }

    /**
     * 
     */
    public function onClose(ConnectionInterface $conn)
    {
        $clientClose = '';

        // search the user
        foreach ($this->clients as $client) {
            if ($conn === $client->conn) {
                // The sender is not the receiver, send to each user connected
                $clientClose = $client;
            }
        }

        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($clientClose);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    /**
     * 
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}