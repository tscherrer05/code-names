<?php
namespace App\RealTime;

use Ratchet\ConnectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Router
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

    }

    public function execute(Action $action, \SplObjectStorage $clients, ConnectionInterface $from)
    {
        $realTimeController = $this->container->get('realtime');

        $arguments = $action->getArguments();
        $arguments['clients'] = $clients;
        $arguments['from'] = $from;
     
        switch($action->getMethod())
        {
            case 'vote':
                return $realTimeController->vote($arguments);
            case 'startGame':
                return $realTimeController->startGame($arguments);
            case 'passTurn':
                return $realTimeController->passTurn($arguments);
            case 'playerConnected':
                return $realTimeController->connectPlayer($arguments);
            default:
                throw new \InvalidArgumentException("Cette action n'est pas possible : " . $action->getMethod());
        }
    }
}