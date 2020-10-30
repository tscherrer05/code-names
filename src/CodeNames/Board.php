<?php
declare(strict_types=1);
namespace App\CodeNames;

use App\Entity\Colors;
use Exception;

class Board
{
    // A two dimensional array representing the cards on board
    private $cards;
    // A single dimension array playerKey => card
    public $votes;

    public $nbColorCards;

    public function __construct(array $cards, array $votes = array())
    {
        $this->cards = $cards;
        $this->votes = $votes;
        $this->nbColorCards = [];
        $this->nbColorCards[Colors::Blue] = count($this->getCards(Colors::Blue));
        $this->nbColorCards[Colors::Red] = count($this->getCards(Colors::Red));
        $this->nbColorCards[Colors::White] = count($this->getCards(Colors::White));
        $this->nbColorCards[Colors::Black] = count($this->getCards(Colors::Black));
    }

    private function getCards(int $color) {
        return array_filter($this->cards, function($c) use($color) {
            return array_filter($c, function($c1) use($color) {
                if($c1->color === $color)
                    return $c1;
            });
        });
    }

    // Command
    public function voteForCard(Player $player, int $x, int $y, GameInfo $gameInfo)
    {
        if(\count($this->cards) <= $x)
            throw new \InvalidArgumentException("Invalid x coord");
        if(\count($this->cards[0]) <= $y)
            throw new \InvalidArgumentException("Invalid y coord");
        $card = $this->cards[$x][$y];
        if($this->isCardReturned($x, $y))
            throw new Exception("Can not return a returned card");
        $this->votes[$player->guid] = $card;
        $everyBodyHasVoted = (\count($this->votes) == $gameInfo->nbPlayers());

        if($everyBodyHasVoted && $this->everybodyVotedForSameCard())
        {
            $this->returnCard($x, $y);
        }
    }

    private function everybodyVotedForSameCard()
    {
        $lastCard = null;
        foreach($this->votes as $card)
        {
            if($lastCard == null)
                $lastCard = $card;
            if($lastCard != $card)
                return false;
        }
        return true;
    }

    public function returnCard(int $x, int $y)
    {
        $card = $this->cards[$x][$y];
        $card->returnMe();
        $this->votes = array();
    }

    public function getCard(int $x, int $y)
    {
        return $this->cards[$x][$y];
    }

    public function isCardReturned(int $x, int $y)
    {
        return $this->cards[$x][$y]->returned;
    }

    // Query
    public function getVotes(int $x, int $y)
    {
        $cardToInspect = $this->cards[$x][$y];
        $result = array();

        foreach($this->votes as $player => $card)
        {
            if($cardToInspect == $card)
                $result[] = $player;
        }
        return $result;
    }

    public function getVote(string $playerKey)
    {
        return $this->votes[$playerKey];
    }

    public function cards()
    {
        return $this->cards;
    }
}
?>