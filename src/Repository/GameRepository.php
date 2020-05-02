<?php
declare(strict_types=1);
namespace App\Repository;
use App\CodeNames\GameInfo;
use App\CodeNames\Board;
use App\CodeNames\Player;
use App\CodeNames\Card;

/**
 * Only class that interacts with database.
 * Builds/saves model object graph.
 * This repository is specific to a json datasource. Replace it if you use another datasource type.
 */
class GameRepository
{
    private $context;
    private $datasource;

    public function __construct()
    {
        $this->datasource = dirname(__DIR__) . '/datasource/data.json';
        $data = \file_get_contents($this->datasource);
        $this->context = \json_decode($data, true);
    }

    // Get a Game object (for command purpose)
    public function get(int $gameId)
    {
        $gameData = $this->getGame($gameId);
        if($gameData == null)
            throw new \Exception('Game not found with id : ' . $gameId);

        $cards = array();
        $votes = array();
        $players = array();

        foreach ($gameData['players'] as $p) {
            $players[] = new Player($p["id"], $p["name"], $p["team"]);
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

    public function addPlayer(int $gameId, string $name, int $team, int $role)
    {
        // TODO : calculate New id 
        $uniqId = 2345;

        array_push($this->context[$gameId]['players'], array(
            "id" => $uniqId,
            "team" => $team,
            "name" => $name,
            "role" => $role
        ));

        $this->save();
        return $uniqId;
    }

    private function getGame(int $gameId)
    {
        return $this->context[$gameId];
    }

    private function save()
    {
        $newJson = \json_encode($this->context);
        \file_put_contents($this->datasource, $newJson);
    }
}

?>