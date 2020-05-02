<?php
declare(strict_types=1);
namespace App\Repository;
use App\CodeNames\GameInfo;
use App\CodeNames\Board;
use App\CodeNames\Player;
use App\CodeNames\Card;

class GameRepository
{
    private $context;

    public function __construct()
    {
        // TODO : inject datasource
        $datasource = dirname(__DIR__) . '/datasource/data.json';
        $data = \file_get_contents($datasource);
        $this->context = \json_decode($data);
    }

    public function create()
    {
        // TODO
    }

    // Get a Game object (for command purpose)
    public function get(int $gameId)
    {
        // We only have one game at a time, so we take the first in the array.
        $gameData = $this->context[0];
        $cards = array();
        $votes = array();
        $players = array();

        foreach ($gameData->players as $p) {
            $players[] = new Player($p->id, $p->name, $p->team);
        }
        foreach ($gameData->cards as $c) {
            $cards[$c->x][$c->y] = new Card($c->word, $c->color, $c->x, $c->y, $c->returned);
        }
        foreach ($gameData->votes as $v) {
            $votes[$v->playerId] = $cards[$v->x][$v->y];
        }

        $board = new Board($cards, $votes);
        $gameInfo = new GameInfo(
            $board, 
            $gameData->currentTeam, 
            $gameData->announcedWord, 
            $gameData->announcedNumber, 
            $players);

        return $gameInfo;
    }

    public function save(GameInfo $game)
    {
        // TODO
    }
}

?>