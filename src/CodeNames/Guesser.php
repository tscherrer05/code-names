<?php
declare(strict_types=1);
namespace App\CodeNames;
use Exception;

class Guesser
{
    function __construct($name, $team)
    {
        $this->name = $name;
        $this->team = $team;
    }

    public $name;
    public $team;
    private $hasAnnounced = false;

    public function announce(GameInfo $gameInfo, Board $board, string $word, int $number)
    {
        if($this->hasAnnounced)
            throw new Exception('Ce devineur a déjà annoncé pour ce tour.');
        $this->hasAnnounced = true;
        $gameInfo->word = $word;
        $gameInfo->number = $number;
    }
}
?>