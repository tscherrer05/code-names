<?php
namespace App\CodeNames;

class GameInfo
{
    function __construct(
        Board $board, 
        int $teamId,
        string $word,
        int $number, 
        array $players)
    {
        $this->team = $teamId;
        $this->nbPlayers = \count($players);
        $this->board= $board;
        $this->word = $word;
        $this->number = $number;
    }

    public $word;
    public $number;
    public $team;
    public $nbPlayers;
    public $board;


    // Query
    public function currentWord()
    {
        return $this->word;
    }

    public function currentNumber()
    {
        return $this->number;
    }

    public function currentTeam()
    {
        return $this->team;
    }

    public function board()
    {
        return $this->board;
    }

    public function winner($board)
    {
        if ($board->nbColorCards[1] == 0)
            return 1;
        else if ($board->nbColorCards[2] == 0)
            return 2;
        else
            return null;
    }


    // Commands
    public function vote(Player $player, int $x, int $y)
    {
        if($this->team != $player->team)
            throw new \InvalidArgumentException("Ce n'est pas le tour du joueur " . $player->name . " (#" . $player->id . ")");
        $this->board->voteForCard($player, $x, $y, $this);
    }

}

?>