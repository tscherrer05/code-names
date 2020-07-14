<?php
namespace App\Tests\CodeNames;
use App\CodeNames\Card as Card;

class TestData
{
    public static function getCards()
    {
        return array (
            array(new Card("orange", 1, 0, 0), new Card("banana", 1, 0, 1), new Card("apple", 1, 0, 2), new Card("aze", 0, 0, 3), new Card("azeaze", 0, 0, 4)),
            array(new Card("prune", 2, 1, 0), new Card("ortie", 0, 1, 1), new Card("planche", 1, 1, 2), new Card("azerty", 3, 1, 3), new Card("zouzi", 0, 1, 4)),
            array(new Card("rigolo", 0, 2, 0), new Card("holala", 2, 2, 1), new Card("Karanba", 1, 2, 2), new Card("wola", 0, 2, 3), new Card("azeroth", 2, 2, 4)),
            array(new Card("Hagar", 0, 3, 0), new Card("pueblo", 1, 3, 1), new Card("usted", 2, 3, 2), new Card("man", 0, 3, 3), new Card("google", 0, 3, 4)),
            array(new Card("linux", 1, 4, 0), new Card("windows", 0, 4, 1), new Card("maque", 0, 4, 2), new Card("endroid", 2, 4, 3), new Card("plaza", 0, 4, 4))
        );
    }

    public static function getCardAlmostWin()
    {
        return array (
            array(new Card("orange", 1, 0, 0, true), new Card("banana", 1, 0, 1, true), new Card("apple", 1, 0, 2, true), new Card("aze", 0, 0, 3), new Card("azeaze", 0, 0, 5)),
            array(new Card("prune", 2, 1, 0), new Card("ortie", 0, 1, 1), new Card("planche", 1, 1, 2, true), new Card("azerty", 3, 1, 3), new Card("zouzi", 0, 1, 4)),
            array(new Card("rigolo", 0, 2, 0), new Card("holala", 2, 2, 1), new Card("Karanba", 1, 2, 2, true), new Card("wola", 0, 2, 3), new Card("azeroth", 2, 2, 4)),
            array(new Card("Hagar", 0, 3, 0), new Card("pueblo", 1, 3, 1, true), new Card("usted", 2, 3, 2), new Card("man", 0, 3, 3), new Card("google", 0, 3, 4)),
            array(new Card("linux", 1, 4, 0), new Card("windows", 0, 4, 1), new Card("maque", 0, 4, 2), new Card("endroid", 2, 4, 3), new Card("plaza", 0, 4, 4))
        );
    }

    public static function getCardWon()
    {
        return array (
            array(new Card("orange", 1, 0, 0, true), new Card("banana", 1, 0, 1, true), new Card("apple", 1, 0, 2, true), new Card("aze", 0, 0, 3), new Card("azeaze", 0, 0, 5)),
            array(new Card("prune", 2, 1, 0), new Card("ortie", 0, 1, 1), new Card("planche", 1, 1, 2, true), new Card("azerty", 3, 1, 3), new Card("zouzi", 0, 1, 4)),
            array(new Card("rigolo", 0, 2, 0), new Card("holala", 2, 2, 1), new Card("Karanba", 1, 2, 2, true), new Card("wola", 0, 2, 3), new Card("azeroth", 2, 2, 4)),
            array(new Card("Hagar", 0, 3, 0), new Card("pueblo", 1, 3, 1, true), new Card("usted", 2, 3, 2), new Card("man", 0, 3, 3), new Card("google", 0, 3, 4)),
            array(new Card("linux", 1, 4, 0, true), new Card("windows", 0, 4, 1), new Card("maque", 0, 4, 2), new Card("endroid", 2, 4, 3), new Card("plaza", 0, 4, 4))
        );
    }
}

?>