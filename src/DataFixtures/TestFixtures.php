<?php

namespace App\DataFixtures;

use App\CodeNames\GameStatus;
use App\Entity\Card;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game;
use App\Entity\GamePlayer;
use App\Entity\Roles;
use App\Entity\Teams;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Ramsey\Uuid\Nonstandard\Uuid;

class TestFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['test'];
    }
 
    const GameKey1 = "ad0abce2-f458-4d02-8cb4-ee3e0df495e6";
    const GameKey2 = "ad0abce2-f458-4d02-8cb4-ee3e0dkdsa0z";
    const GameKey3 = "ad0abce2-f458-4d02-8cb4-ee3e0dkdsdc9";
    const PlayerKey1 = "299c6679-62a9-43d0-9a28-4299d25672eb";
    const PlayerKey2 = "900c6679-62a9-43d0-9a28-4299d25672ai";
    const PlayerKey3 = "900c6679-62a9-43d0-9a28-4299d25609ja";
    const PlayerKey4 = "900c6679-62a9-43d0-9a28-4299d21323lm";
    const PlayerKey5 = "900c6679-62a9-43d0-9a28-4299d21324ds";
    const PlayerKey6 = "900c6679-62a9-43d0-9a28-4299d213x0sc";
    const PlayerKey7 = "900c6679-62a9-43d0-9a28-4299d2131csq";
    const PlayerKey8 = "900c6679-62a9-43d0-9a28-4299d2130dsq";
    const Cards = [
        ['orange', 0, 0, 1],
        ['chimpanzé', 0, 1, 2],
        ['orteil', 0, 2, 2],
        ['courgette', 0, 3, 1]
    ];

    public function load(ObjectManager $manager)
    {
        $manager->persist($this->createOnGoingGame($manager));
        $manager->persist($this->createLobbyGame($manager));
        $manager->persist($this->createEmptyGame($manager));
        $manager->flush();
    }

    private function createEmptyGame(ObjectManager $manager) 
    {
        $game = new Game();
        $game->setPublicKey(self::GameKey3);
        $game->setStatus(GameStatus::OnGoing);
        $game->setCurrentWord(null);
        $game->setCurrentNumber(null);
        $game->setCurrentTeam(null);

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
        return $game;
    }

    private function createOnGoingGame(ObjectManager $manager) 
    {
        $game = new Game();
        $game->setPublicKey(self::GameKey1);
        $game->setStatus(GameStatus::OnGoing);
        $game->setCurrentWord('Acme');
        $game->setCurrentNumber(42);
        $game->setCurrentTeam(Teams::Blue);

        // player
        $this->createFakeSpy($manager, $game, 'Spy'.self::PlayerKey1, self::PlayerKey1);
        $this->createFakeSpy($manager, $game, 'Spy'.self::PlayerKey3, self::PlayerKey3);
        $this->createFakeSpy($manager, $game, 'Spy'.self::PlayerKey4, self::PlayerKey4);
        $this->createFakeMaster($manager, $game, 'Player2', self::PlayerKey2);

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
        return $game;
    }

    private function createLobbyGame(ObjectManager $manager) 
    {
        $game = new Game();
        $game->setPublicKey(self::GameKey2);
        $game->setStatus(GameStatus::Lobby);
        $game->setCurrentWord(null);
        $game->setCurrentNumber(null);
        $game->setCurrentTeam(null);

        // player
        $this->createFakeSpy($manager, $game, 'Spy'.self::PlayerKey5, self::PlayerKey5);
        $this->createFakeSpy($manager, $game, 'Spy'.self::PlayerKey6, self::PlayerKey6);
        $this->createFakeSpy($manager, $game, 'Spy'.self::PlayerKey7, self::PlayerKey7);
        $this->createFakeMaster($manager, $game, 'Master'.self::PlayerKey8, self::PlayerKey8);

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
        return $game;
    }

    private function createFakeSpy(ObjectManager $manager,
        Game $game, string $name, string $playerKey)
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setName($name);
        $gamePlayer->setPublicKey($playerKey);
        $gamePlayer->setSessionId(Uuid::uuid1()->toString());
        $gamePlayer->setTeam(Teams::Blue);
        $gamePlayer->setRole(Roles::Spy);
        $gamePlayer->setX(null);
        $gamePlayer->setY(null);
        $manager->persist($gamePlayer);
    }

    private function createFakeMaster(ObjectManager $manager,
        Game $game, string $name, string $playerKey)
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setName($name);
        $gamePlayer->setPublicKey($playerKey);
        $gamePlayer->setSessionId(Uuid::uuid1()->toString());
        $gamePlayer->setTeam(Teams::Blue);
        $gamePlayer->setRole(Roles::Master);
        $gamePlayer->setX(null);
        $gamePlayer->setY(null);
        $manager->persist($gamePlayer);
    }
}
