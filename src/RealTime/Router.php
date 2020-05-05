<?php
namespace App\RealTime;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Router
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function execute(Action $action)
    {
        $realTimeController = $this->container->get('realtime');
     
        switch($action->getMethod())
        {
            case 'vote':
                return $realTimeController->vote($action->getArguments());
            case 'returnCard':
                return $realTimeController->returnCard($action->getArguments());
            default:
                throw new \InvalidArgumentException("Cette action n'est pas possible.");
        }
    }
}