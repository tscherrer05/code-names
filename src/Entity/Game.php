<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GameRepository")
 */
class Game
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="guid")
     */
    private $publicKey;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $currentWord;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $currentNumber;

    /**
     * @ORM\Column(type="smallint", nullable=true)
     */
    private $currentTeam;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Card", mappedBy="game", fetch="EAGER")
     */
    private $cards;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\GamePlayer", mappedBy="game", fetch="EAGER")
     */
    private $gamePlayers;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
        $this->gamePlayers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }

    public function setPublicKey(string $publicKey): self
    {
        $this->publicKey = $publicKey;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCurrentWord(): ?string
    {
        return $this->currentWord;
    }

    public function setCurrentWord(?string $currentWord): self
    {
        $this->currentWord = $currentWord;

        return $this;
    }

    public function getCurrentNumber(): ?int
    {
        return $this->currentNumber;
    }

    public function setCurrentNumber(?int $currentNumber): self
    {
        $this->currentNumber = $currentNumber;

        return $this;
    }

    public function getCurrentTeam(): ?int
    {
        return $this->currentTeam;
    }

    public function setCurrentTeam(?int $currentTeam): self
    {
        $this->currentTeam = $currentTeam;

        return $this;
    }

    /**
     * @return Collection|Card[]
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function addCard(Card $card): self
    {
        if (!$this->cards->contains($card)) {
            $this->cards[] = $card;
            $card->setGame($this);
        }

        return $this;
    }

    public function removeCard(Card $card): self
    {
        if ($this->cards->contains($card)) {
            $this->cards->removeElement($card);
            // set the owning side to null (unless already changed)
            if ($card->getGame() === $this) {
                $card->setGame(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|GamePlayer[]
     */
    public function getGamePlayers(): Collection
    {
        return $this->gamePlayers;
    }

    public function addGamePlayer(GamePlayer $gamePlayer): self
    {
        if (!$this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers[] = $gamePlayer;
            $gamePlayer->setGame($this);
        }

        return $this;
    }

    public function removeGamePlayer(GamePlayer $gamePlayer): self
    {
        if ($this->gamePlayers->contains($gamePlayer)) {
            $this->gamePlayers->removeElement($gamePlayer);
            // set the owning side to null (unless already changed)
            if ($gamePlayer->getGame() === $this) {
                $gamePlayer->setGame(null);
            }
        }

        return $this;
    }
}
