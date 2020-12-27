<?php
namespace App\CodeNames;

use App\Entity\Roles;
use App\Entity\Teams;

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

    /**
     * Gets a player from its player key
     */
    public function getPlayer(string $playerKey)
    {
        return array_values(array_filter($this->players, function($p) use($playerKey) 
        {
            return $p->guid === $playerKey;
        }))[0];
    }

    /**
     * Number of spies in the game/team
     */
    public function nbSpies($team = null)
    {
        return \count(array_filter($this->players, function($p) use($team) {
            if($p->role === Roles::Spy)
                if($team === null || $team != null && $team === $p->team)
                    return $p;
        }));
    }

    /**
     * All votes in a single dim array (playerKey => card)
     */
    public function getAllVotes()
    {
        return $this->board()->votes;
    }

    /**
     * Get all cards from the board
     */
    public function getAllCards()
    {
        return $this->board()->cards();
    }

    /**
     * Gets the winner. Null if no winner.
     */
    public function winner($board)
    {
        if ($board->nbColorCards[Teams::Blue] == 0)
            return Teams::Blue;
        else if ($board->nbColorCards[Teams::Red] == 0)
            return Teams::Red;
        else
            return null;
    }

    /**
     * Make a player vote for a card (from its coordinates)
     */
    public function vote(Player $player, int $x, int $y)
    {
        if($this->team != $player->team)
            return "WrongTurn";
        $this->board->voteForCard($player, $x, $y, $this);
        return [
            'ok' => true,
            'card' => $this->board()->getCard($x, $y),
        ];
    }

    /**
     * Add a player into the game.
     * Throws if the player can not be added.
     */
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

    /**
     * Passes the turn to the opposite team.
     */
    public function passTurn()
    {
        $this->team = $this->team === 1 ? 2 : 1;
    }

    private function board()
    {
        return $this->board;
    }
}

?>