<?php
declare(strict_types=1);
namespace App\CodeNames;

class Player
{
    public $name;

    public $team;

    public $role;

    // Unique id (in whole application) to identify a player
    public $guid;

    function __construct(string $guid, string $name, int $team = null, int $role = null)
    {
        $this->guid = $guid;
        $this->name = $name;
        $this->team = $team;
        $this->role = $role;
    }
}

?>