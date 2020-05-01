<?php
namespace App\CodeNames;

class Card
{
    public $color;
    public $word;
    public $returned = false;
    public $x;
    public $y;

    public function __construct(string $word, int $color, int $x, int $y, bool $returned = false)
    {
        $this->word = $word;
        $this->color = $color;
        $this->returned = $returned;
        $this->x = $x;
        $this->y = $y;
    }

    public function returnMe()
    {
        $this->returned = true;
    }

    public function __is_equal(Card $otherCard)
    {
        return $this->word == $otherCard->word;
    }
}

?>