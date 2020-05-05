<?php
declare(strict_types=1);
namespace App\Repository;
use App\CodeNames\GameInfo;
use App\CodeNames\Board;
use App\CodeNames\Player;
use App\CodeNames\Card;
use Ramsey\Uuid\Uuid;


/**
 * Only class that interacts with database.
 * Builds/saves model objects graph.
 * This repository is specific to a json datasource. Replace it if you use another datasource type.
 */
class GameInfoRepository
{
    private $context;
    private $datasource;

    public function __construct()
    {
        $this->datasource = dirname(__DIR__) . '/datasource/data.json';
        $data = \file_get_contents($this->datasource);
        $this->context = \json_decode($data, true);
    }

    /**
     * Get a Game as graph root object.
     */
    public function get(int $gameId)
    {
        $gameData = $this->context[$gameId];
        if($gameData == null)
            throw new \Exception('Game not found with id : ' . $gameId);

        $cards = array();
        $votes = array();
        $players = array();

        foreach ($gameData['players'] as $p) {
            $players[] = new Player($p["id"], $p["name"], $p["team"], $p["role"]);
        }
        foreach ($gameData['cards'] as $c) {
            $cards[$c['x']][$c['y']] = new Card($c['word'], $c['color'], $c['x'], $c['y'], $c['returned']);
        }
        foreach ($gameData['votes'] as $v) {
            $votes[$v['playerId']] = $cards[$v['x']][$v['y']];
        }

        $board = new Board($cards, $votes);
        $gameInfo = new GameInfo(
            $board, 
            $gameData['currentTeam'], 
            $gameData['announcedWord'], 
            $gameData['announcedNumber'], 
            $players);

        return $gameInfo;
    }

    /**
     * Creates a transaction to add a player to a game.
     */
    public function addPlayer(int $gameId, string $name, int $team, int $role)
    {
        $uniqId = Uuid::uuid1();

        array_push($this->context[$gameId]['players'], [
            'id' => $uniqId,
            'team' => $team,
            'name' => $name,
            'role' => $role
        ]);

        return $uniqId;
    }

    /**
     * Creates a transaction to add a vote in a game.
     */
    public function addVote(int $gameId, int $playerId, int $x, int $y)
    {
        array_push($this->context[$gameId]['votes'], [
            'playerId' => $playerId,
            'x' => $x,
            'y' => $y
        ]);
    }

    /**
     * Commits all transactions
     */
    public function commit()
    {
        $newJson = \json_encode($this->context);
        \file_put_contents($this->datasource, $newJson);
    }
}

?>