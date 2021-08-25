<?php
namespace App\CodeNames;

class Card
{
    public int $color;
    public string $word;
    public bool $returned = false;
    public int $x;
    public int $y;

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

    public function setX(int $i)
    {
        $this->x = $i;
    }

    public function setY(int $j)
    {
        $this->y = $j;
    }

    public function setWord(string $word)
    {
        $this->word = $word;
    }

    public function setColor($color)
    {
        $this->color = $color;
    }

    public function setReturned(bool $returned)
    {
        $this->returned = $returned;
    }

    /**
     * @return int
     */
    public function getColor(): int
    {
        return $this->color;
    }

    /**
     * @return string
     */
    public function getWord(): string
    {
        return $this->word;
    }

    /**
     * @return bool
     */
    public function isReturned(): bool
    {
        return $this->returned;
    }

    /**
     * @return int
     */
    public function getX(): int
    {
        return $this->x;
    }

    /**
     * @return int
     */
    public function getY(): int
    {
        return $this->y;
    }
}