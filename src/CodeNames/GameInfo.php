<?php
namespace App\CodeNames;

class GameInfo
{
    function __construct(
        Board $board, 
        int $teamId = null,
        string $word = null,
        int $number = null, 
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
    
    public $guid;
    public $status;

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

    public function getGuid()
    {
        return $this->guid;   
    }

    public function getPlayer(string $playerKey)
    {
        foreach ($this->players as $player) {
            if($player->guid == $playerKey)
                return $player;
        }
        throw new \Exception('Player not found with id : ' . $playerKey);
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

    public function addPlayer(string $name, int $team = null, int $role = null)
    {
        $player = new Player(0, $name, $team, $role);
        array_push($this->players, $player);
    }

}

?>