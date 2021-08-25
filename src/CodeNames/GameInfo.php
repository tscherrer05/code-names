<?php
namespace App\CodeNames;

use App\Entity\Colors;
use App\Entity\Roles;
use App\Entity\Teams;
use App\Service\Random;
use Exception;
use Ramsey\Uuid\Uuid;

class GameInfo
{
    function __construct(
        Board $board,
        array $players,
        int $teamId = null,
        string $word = null,
        int $number = null)
    {
        $this->team = $teamId;
        $this->players = $players;
        $this->board= $board;
        $this->word = $word;
        $this->number = $number;
    }

    public ?string $word;
    public ?int $number;
    public ?int $team;

    private Board $board;
    private array $players = array();

    public ?string $guid;
    public ?int $status;
    public ?int $id;

    /**
     * @param Random $random
     * @param array $numbers
     * @return int
     */
    private static function chooseRandColor(Random $random, array $numbers): int
    {
        return $random->rand(0, \count($numbers) - 1);
    }

    /**
     * @param Random $random
     * @param array $excludedWords
     * @return string
     */
    private static function chooseRandWord(Random $random, array $excludedWords): string
    {
        return $random->word($excludedWords);
    }

    public function currentWord(): ?string
    {
        return $this->word;
    }

    public function currentNumber(): ?int
    {
        return $this->number;
    }

    public function currentTeam(): ?int
    {
        return $this->team;
    }

    public function getPlayers()
    {
        return $this->players;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getGuid()
    {
        return $this->guid;
    }

    public function getId()
    {
        return $this->id;
    }

    public static function brandNew(Random $random) : GameInfo
    {
        $numbers = [
            [Colors::Blue, 9],
            [Colors::Red, 8],
            [Colors::White, 7],
            [Colors::Black, 1],
        ];
        $rows = 5;
        $cols = 5;
        $excludedWords = [];
        $cards = [];

        for($i = 0; $i <= $cols - 1; $i++) {
            for($j = 0; $j <= $rows - 1; $j++) {
                $word = self::chooseRandWord($random, $excludedWords);
                $excludedWords[] = $word;
                $index = self::chooseRandColor($random, $numbers);
                $choice = $numbers[$index];
                $color = $choice[0];
                $number = $choice[1];
                if($number === 1) {
                    array_splice($numbers, $index, 1);
                } else {
                    $numbers[$index][1]--;
                }
                $cards[$i][] = new Card($word, $color, $i, $j);
            }
        }

        $board = new Board($cards);
        $gameInfo = new GameInfo($board, []);
        $gameInfo->guid = Uuid::uuid1()->toString();
        $gameInfo->status = GameStatus::OnGoing;
        $gameInfo->team = Teams::Blue;

        return $gameInfo;
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
    public function getAllCards(): array
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
            return [
                'ok' => false,
            ];
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
                throw new Exception("Il y a déjà un maître espion dans cette équipe.");
        }
        $this->players[] = new Player($guid, $name, $team, $role);
    }

    /**
     * Passes the turn to the opposite team.
     */
    public function passTurn()
    {
        $this->team = $this->team === Teams::Blue ? Teams::Red : Teams::Blue;
    }

    private function board()
    {
        return $this->board;
    }

}

?>