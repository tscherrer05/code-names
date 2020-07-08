<?php

namespace App\DataFixtures;

use App\CodeNames\GameStatus;
use App\Entity\Card;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game;
use App\Entity\Player;
use App\Entity\GamePlayer;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Ramsey\Uuid\Nonstandard\Uuid;

class DefaultFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['test'];
    }
 
    const GameKey1 = "ad0abce2-f458-4d02-8cb4-ee3e0df495e6";
    const PlayerKey1 = "299c6679-62a9-43d0-9a28-4299d25672eb";
    const PlayerKey2 = "900c6679-62a9-43d0-9a28-4299d25672ai";
    const Cards = [
        ['orange', 0, 0, 1],
        ['chimpanzé', 0, 1, 2],
        ['orteil', 0, 2, 2],
        ['courgette', 0, 3, 1]
    ];

    public function load(ObjectManager $manager)
    {
        // A game in lobby state
        $game = new Game();
        $game->setPublicKey(self::GameKey1);
        $game->setStatus(GameStatus::Lobby);
        $game->setCurrentWord('Acme');
        $game->setCurrentNumber(42);
        $game->setCurrentTeam(1);

        // player
        $this->createFakePlayer($manager, $game, 'Player1', self::PlayerKey1);
        $this->createFakePlayer($manager, $game, 'Player2', self::PlayerKey2);

        // card
        $dataCards = self::Cards;
        foreach ($dataCards as $value) {
            $card = new Card();
            $card->setWord($value[0]);
            $card->setX($value[1]);
            $card->setY($value[2]);
            $card->setGame($game);
            $card->setColor($value[3]);
            $card->setReturned(false);
            $manager->persist($card);
        }

        $manager->persist($game);
        $manager->flush();
    }

    private function createFakePlayer(ObjectManager $manager,
        Game $game, string $name, string $playerKey)
    {
        $player = new Player();
        $player->setName($name);
        $player->setPlayerKey($playerKey);

        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setPlayer($player);
        $gamePlayer->setSessionId(Uuid::uuid1()->toString());
        $gamePlayer->setTeam(1);

        $manager->persist($player);
        $manager->persist($gamePlayer);
    }
}
