<?php
namespace App\CodeNames;

use App\Entity\Roles;

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
    public $id;

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

    public function getId()
    {
        return $this->id;
    }

    public function getPlayer(string $playerKey)
    {
        return array_values(array_filter($this->players, function($p) use($playerKey) 
        {
            return $p->guid === $playerKey;
        }))[0];
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
            throw new \InvalidArgumentException("Ce n'est pas le tour du joueur " . $player->name . " (#" . $player->guid . ")");
        $this->board->voteForCard($player, $x, $y, $this);
    }

    public function addPlayer(string $guid, string $name, int $team = null, int $role = null)
    {
        if($role == Roles::Master) 
        {
            $masters = array_filter($this->players, 
                function($p) use($team) 
                {
                    if($p->role == Roles::Master && $p->team == $team)
                        return $p;
                }
            );

            if(count($masters) >= 1)
                throw new \Exception("Il y a déjà un maître espion dans cette équipe.");
        }
        $this->players[] = new Player($guid, $name, $team, $role);
    }

    public function passTurn()
    {
        $this->team = $this->team === 1 ? 2 : 1;
    }
}

?>