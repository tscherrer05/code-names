<?php
declare(strict_types=1);
namespace App\CodeNames;

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
        // TODO : Init nbColorCards
        $this->nbColorCards = array(
            0 => 9, // White
            1 => 8, // Blue
            2 => 8, // Red
            3 => 1  // Black
        );
    }

    // Command
    public function voteForCard(Player $player, int $x, int $y, GameInfo $gameInfo)
    {
        if(\count($this->cards) <= $x)
            throw new \InvalidArgumentException();
        if(\count($this->cards[0]) <= $y)
            throw new \InvalidArgumentException();
        $this->votes[$player->guid] = $this->cards[$x][$y];
        $everyBodyHasVoted = (\count($this->votes) == $gameInfo->nbPlayers());
        $lastCard = null;

        if($everyBodyHasVoted)
        {
            foreach($this->votes as $card)
            {
                if($lastCard == null)
                    $lastCard = $card;
                if($lastCard != $card)
                    return;
            }

            $this->returnCard($x, $y);
        }
    }

    public function returnCard(int $x, int $y)
    {
        $card = $this->cards[$x][$y];
        $card->returnMe();
        $this->nbColorCards[$card->color]--;
        $this->votes = array();
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

    public function cards()
    {
        return $this->cards;
    }
}
?>