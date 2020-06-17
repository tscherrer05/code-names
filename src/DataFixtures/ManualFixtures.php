<?php

namespace App\DataFixtures;

use App\CodeNames\GameStatus;
use App\Entity\Card;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game;

use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;


class ManualFixtures extends Fixture implements FixtureGroupInterface
{
    public static function getGroups(): array
    {
        return ['manual'];
    }

    const GameKey1 = "ad0abce2-f458-4d02-8cb4-ee3e0df495e6";
    const PlayerKey1 = "299c6679-62a9-43d0-9a28-4299d25672eb";

    public function load(ObjectManager $manager)
    {
        // A game in lobby state
        $game = new Game();
        $game->setPublicKey(self::GameKey1);
        $game->setStatus(GameStatus::Lobby);

        // card
        $dataCards = [
            // Ligne 1
            ['orange',      0, 0, 1],
            ['chimpanzé',   0, 1, 2],
            ['orteil',      0, 2, 2],
            ['courgette',   0, 3, 0],
            ['potiron',   0, 4, 0],

            // Ligne 2
            ['courgette',   1, 0, 1],
            ['télévision',   1, 1, 2],
            ['patate',   1, 2, 1],
            ['chat',   1, 3, 1],
            ['courgette',   1, 4, 2],

            // Ligne 3
            ['peinture',   2, 0, 1],
            ['fraise',   2, 1, 3],
            ['armoire',   2, 2, 2],
            ['cortège',   2, 3, 1],
            ['multiplication',   2, 4, 1],

            // Ligne 4
            ['poubelle',   3, 0, 1],
            ['portable',   3, 1, 2],
            ['maximum',   3, 2, 0],
            ['poterie',   3, 3, 1],
            ['rome',   3, 4, 1],

            // Ligne 5
            ['Potentiel',   4, 0, 1],
            ['Serviette',   4, 1, 2],
            ['Pied',   4, 2, 3],
            ['Feuille',   4, 3, 0],
            ['Voiture',   4, 4, 1],
        ];
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
}
