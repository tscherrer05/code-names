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
        return $realTimeController->vote($action->getArguments());
        // return $reflectionMethod->invoke(new Controller(), $action->getArguments());
    }
}