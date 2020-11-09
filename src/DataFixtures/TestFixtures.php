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
 
    /**
     * Ongoing game
     */
    const GameKey1 = "ad0abce2-f458-4d02-8cb4-ee3e0df495e6";
    /**
     * Lobby game
     */
    const GameKey2 = "ad0abce2-f458-4d02-8cb4-ee3e0dkdsa0z";
    /**
     * Empty game
     */
    const GameKey3 = "ad0abce2-f458-4d02-8cb4-ee3e0dkdsdc9";


    const PlayerKey1 = "299c6679-62a9-43d0-9a28-4299d25672eb";
    const PlayerKey2 = "900c6679-62a9-43d0-9a28-4299d25672ai";
    const PlayerKey3 = "900c6679-62a9-43d0-9a28-4299d25609ja";
    const PlayerKey4 = "900c6679-62a9-43d0-9a28-4299d21323lm";
    const PlayerKey5 = "900c6679-62a9-43d0-9a28-4299d21324ds";
    const PlayerKey6 = "900c6679-62a9-43d0-9a28-4299d213x0sc";
    const PlayerKey7 = "900c6679-62a9-43d0-9a28-4299d2131csq";
    const PlayerKey8 = "900c6679-62a9-43d0-9a28-4299d2130dsq";
    const PlayerKey9 = "900c6679-62a9-43d0-9a28-4299d211dzsx";
    
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
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Spy, 'Spy'.self::PlayerKey1, self::PlayerKey1);
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Spy, 'Spy'.self::PlayerKey3, self::PlayerKey3);
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Spy,'Spy'.self::PlayerKey4, self::PlayerKey4);
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Master,'Player2', self::PlayerKey2);
        $this->createFakePlayer($manager, $game, Teams::Red, Roles::Master,'Player2', self::PlayerKey9);

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
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Spy, 'Spy'.self::PlayerKey5, self::PlayerKey5);
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Spy, 'Spy'.self::PlayerKey6, self::PlayerKey6);
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Spy, 'Spy'.self::PlayerKey7, self::PlayerKey7);
        $this->createFakePlayer($manager, $game, Teams::Blue, Roles::Master, 'Master'.self::PlayerKey8, self::PlayerKey8);

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

    private function createFakePlayer(ObjectManager $manager,
        Game $game, int $team, int $role, string $name, string $playerKey)
    {
        $gamePlayer = new GamePlayer();
        $gamePlayer->setGame($game);
        $gamePlayer->setName($name);
        $gamePlayer->setPublicKey($playerKey);
        $gamePlayer->setSessionId(Uuid::uuid1()->toString());
        $gamePlayer->setTeam($team);
        $gamePlayer->setRole($role);
        $gamePlayer->setX(null);
        $gamePlayer->setY(null);
        $manager->persist($gamePlayer);
    }
}
