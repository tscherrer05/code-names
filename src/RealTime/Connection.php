<?php

namespace App\RealTime;

use Ramsey\Uuid\Guid\Guid;
use Ratchet\ConnectionInterface;

class Connection
{
    function __construct($gamePlayerKey, $connectionInterface)
    {
        $this->gamePlayerKey = $gamePlayerKey;
        $this->connectionInterface = $connectionInterface;
    }

    public Guid $gamePlayerKey;
    public ConnectionInterface $connectionInterface;
}