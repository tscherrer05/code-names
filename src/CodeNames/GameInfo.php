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
        $this->players = $players;
        $this->board= $board;
        $this->word = $word;
        $this->number = $number;
    }

    public $word;
    public $number;
    public $team;
    
    private $board;
    private $players = array();

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

    public function getPlayers()
    {
        return $this->players;
    }

    public function getPlayer(string $id)
    {
        foreach ($this->players as $player) {
            if($player->id == $id)
                return $player;
        }
        throw new \Exception('Player not found with id : ' . $id);
    }

    public function nbPlayers()
    {
        return \count($this->players);
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

    public function addPlayer(string $name, int $team, int $role)
    {
        $player = new Player(0, $name, $team, $role);
        array_push($this->players, $player);
    }

}

?>