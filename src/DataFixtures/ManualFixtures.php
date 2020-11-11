<?php

namespace App\DataFixtures;

use App\CodeNames\GameStatus;
use App\Entity\Card;
use App\Entity\Colors;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\Game;
use App\Entity\Teams;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use PHPUnit\Util\Color;

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
        // A game in set up state
        $game = new Game();
        $game->setPublicKey(self::GameKey1);
        $game->setStatus(GameStatus::OnGoing);
        $game->setCurrentWord(null);
        $game->setCurrentNumber(null);
        $game->setCurrentTeam(1);

        // card
        $dataCards = [
            // Ligne 1
            ['orange',      0, 0, Colors::Blue],
            ['chimpanzé',   0, 1, Colors::Red],
            ['orteil',      0, 2, Colors::Red],
            ['courgette',   0, 3, Colors::White],
            ['potiron',     0, 4, Colors::White],

            // Ligne 2
            ['courgette',   1, 0, Colors::White],
            ['télévision',   1, 1, Colors::Red],
            ['patate',   1, 2, Colors::Blue],
            ['chat',   1, 3, Colors::Blue],
            ['courgette',   1, 4, Colors::White],

            // Ligne 3
            ['peinture',   2, 0, Colors::Red],
            ['fraise',   2, 1, Colors::Black],
            ['armoire',   2, 2, Colors::Red],
            ['cortège',   2, 3, Colors::Blue],
            ['multiplication',   2, 4, Colors::Blue],

            // Ligne 4
            ['poubelle',   3, 0, Colors::Blue],
            ['portable',   3, 1, Colors::Red],
            ['maximum',   3, 2, Colors::White],
            ['poterie',   3, 3, Colors::Blue],
            ['rome',   3, 4, Colors::Blue],

            // Ligne 5
            ['Potentiel',   4, 0, Colors::Red],
            ['Serviette',   4, 1, Colors::Red],
            ['Pied',   4, 2, Colors::Red],
            ['Feuille',   4, 3, Colors::White],
            ['Voiture',   4, 4, Colors::Blue],
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
