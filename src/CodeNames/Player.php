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

    function __construct(int $id, string $name, int $team)
    {
        // TODO : throw exception if invalid values
        $this->id = $id;
        $this->name = $name;
        $this->team = $team;
    }
}

?>