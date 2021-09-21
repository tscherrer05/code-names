<?php
namespace App\RealTime;

use Error;
use Exception;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use SplObjectStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function json_encode;

class Messager implements MessageComponentInterface
{
    protected SplObjectStorage $clients;
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->clients = new SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        // Store the new connection to send messages to later
        $this->clients->attach($conn);

        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        echo sprintf('%d sent message "%s"' . "\n", $from->resourceId, $msg);

        try
        {
            // 1. Parse message
            $action = Action::create($msg);

            // 2. Execute corresponding action
            (new Router($this->container))->execute($action, $this->clients, $from);
        }
        catch(Exception $e)
        {
            echo "An error has occurred: {$e->getMessage()}\n";
            $err = json_encode(array("data" => "Une erreur s'est produite."));
            $from->send($err);
        }
        catch(Error $e)
        {
            echo "A fatal error has occurred: {$e->getMessage()}\n";
            $err = json_encode(array("data" => "Une erreur s'est produite."));
            $from->send($err);
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        // The connection is closed, remove it, as we can no longer send it messages
        $this->clients->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }
}