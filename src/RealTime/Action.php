<?php
namespace App\RealTime;

// Action to execute with parameters
class Action
{
    private $json = "";

    public function __construct(string $message)
    {
        $this->json = \json_decode($message, true);
        // TODO : verify structure
    }

    public function getMethod()
    {
        return $this->json["action"];
    }

    public function getArguments()
    {
        return $this->json["parameters"];
    }
}

