<?php
namespace App\RealTime;

class Router
{
    public static function execute(Action $action)
    {
        $reflectionMethod = new \ReflectionMethod('CodeNames\Controller', $action->getMethod());
        return $reflectionMethod->invoke(new Controller(), $action->getArguments());
    }
}