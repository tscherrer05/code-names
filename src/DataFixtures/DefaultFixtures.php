<?php

namespace App\DataFixtures;

use App\CodeNames\GameStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game;

class DefaultFixtures extends Fixture
{
    const GameKey1 = "ad0abce2-f458-4d02-8cb4-ee3e0df495e6";
    const PlayerKey1 = "299c6679-62a9-43d0-9a28-4299d25672eb";

    public function load(ObjectManager $manager)
    {
        // A game in lobby state
        $game = new Game();
        $game->setPublicKey(self::GameKey1);
        $game->setStatus(GameStatus::Lobby);
        
        // // player
        // $player = new Player();
        // $player->setName('Tim');
        // $player->setPlayerKey(self::PlayerKey1);

        // // game player
        // $gamePlayer = new GamePlayer();
        // $gamePlayer->setGame($game);
        // $gamePlayer->setPlayer($player);
        
        $manager->persist($game);
        // $manager->persist($player);
        // $manager->persist($gamePlayer);
        $manager->flush();
    }
}
