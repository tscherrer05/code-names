<?php
declare(strict_types=1);
namespace App\CodeNames;

class Board
{
    // A two dimensional array representing the cards on board
    private $cards;
    // A single dimension array playerId => card
    private $votes;

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
        $this->votes[$player->id] = $this->cards[$x][$y];
        $everyBodyHasVoted = (\count($this->votes) == $gameInfo->nbPlayers);
        $lastCard = null;

        if($everyBodyHasVoted)
        {
            foreach($this->votes as $playerId => $card)
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
        $this->cards[$x][$y]->returnMe();
        $this->nbColorCards[$this->cards[$x][$y]->color]--;
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