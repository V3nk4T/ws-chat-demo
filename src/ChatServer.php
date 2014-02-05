<?php

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface
{
    private $loop;
    private $usernames;
    private $clients;
    private $lines;
    private $max_lines_recorded;

    public function __construct($loop, $max_lines_recorded = 20)
    {
        $this->loop = $loop;
        $this->usernames = array();
        $this->clients = new \SplObjectStorage();
        $this->lines = array();
        $this->max_lines_recorded = $max_lines_recorded;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        error_log("New connection {$conn->resourceId}");

        $this->clients->attach($conn);
        $this->sendLines($conn);
    }  

    public function onMessage(ConnectionInterface $conn, $data)
    {
        error_log("Received: " . $data);

        $msg = json_decode($data);

        switch($msg->type) {
        case 'join':
            $this->join($conn, $msg->name);
            break;
        case 'talk':
            $username = $this->getUsername($conn);
            if (empty($username))
                return;
            $this->talk("$username said: {$msg->msg}");
            break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        error_log("Closing connection {$conn->resourceId}");
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        error_log("Error on connection {$conn->resourceId}: " . $e->getMessage());
        $conn->close();
    }
    
    private function join($conn, $name)
    {
        $hash = spl_object_hash($conn);
        $this->usernames[$hash] = $name;
        $this->talk("$name has joined");
    }

    private function getUsername($conn)
    {
        $hash = spl_object_hash($conn);
        return (isset($this->usernames[$hash])) ? $this->usernames[$hash] : null;
    }

    private function talk($msg)
    {
        $line = $this->getTimestamp() . ' ' . htmlentities($msg);

        $this->sendLine($this->clients, $line);

        $this->recordLine($line);
    }

    private function getTimestamp()
    {
        return '[' . date('c') . ']';
    }

    private function recordLine($line)
    {
        array_unshift($this->lines, $line);
        $this->lines = array_slice($this->lines, 0, $this->max_lines_recorded);
    }
    
    private function sendLines($conn)
    {
        foreach($this->lines as $line) {
            $this->sendLine($conn, $line);
        }
    }

    private function sendLine($conn, $line)
    {
        $this->send($conn, array('type' => 'said', 'line' => $line));
    }

    private function send($conn, $data)
    {
        $msg = json_encode($data, true);

        if (is_array($conn) || $conn instanceof \SplObjectStorage)
        {
            foreach($conn as $c) {
                $c->send($msg);
            }
            return;
        }

        $conn->send($msg);
    }

}
