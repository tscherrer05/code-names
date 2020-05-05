<?php
declare(strict_types=1);
namespace App\CodeNames;

class Player
{
    // Unique id (in whole application) to identify a player
    public $id;
    public $name;

    // Team can be 1 or 2
    public $team;

    // Role can be 1 or 2
    public $role;

    function __construct(string $id, string $name, int $team, int $role)
    {
        // TODO : throw exception if invalid values
        $this->id = $id;
        $this->name = $name;
        $this->team = $team;
        $this->role = $role;
    }
}

?>