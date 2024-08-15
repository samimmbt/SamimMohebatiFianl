<?php

namespace App\Entity;

use App\Repository\GameRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    private ?User $player1Id = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    private ?User $player2Id = null;

    #[ORM\ManyToOne(inversedBy: 'winnerId')]
    private ?User $winnerId = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /**
     * @var Collection<int, Move>
     */
    #[ORM\OneToMany(targetEntity: Move::class, mappedBy: 'game')]//for recreating whole game
    private Collection $moves;

    public function __construct()
    {
        $this->moves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer1Id(): ?User
    {
        return $this->player1Id;
    }

    public function setPlayer1Id(?User $player1Id): static
    {
        $this->player1Id = $player1Id;

        return $this;
    }

    public function getPlayer2Id(): ?User
    {
        return $this->player2Id;
    }

    public function setPlayer2Id(?User $player2Id): static
    {
        $this->player2Id = $player2Id;

        return $this;
    }

    public function getWinnerId(): ?User
    {
        return $this->winnerId;
    }

    public function setWinnerId(?User $winnerId): static
    {
        $this->winnerId = $winnerId;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection<int, Move>
     */
    public function getMoves(): Collection
    {
        return $this->moves;
    }

    public function addMove(Move $move): static
    {
        if (!$this->moves->contains($move)) {
            $this->moves->add($move);
            $move->setGame($this);
        }

        return $this;
    }

    public function removeMove(Move $move): static
    {
        if ($this->moves->removeElement($move)) {
            // set the owning side to null (unless already changed)
            if ($move->getGame() === $this) {
                $move->setGame(null);
            }
        }

        return $this;
    }
}
