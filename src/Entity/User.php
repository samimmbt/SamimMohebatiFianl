<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private $username;

    #[ORM\Column(type: 'string', length: 255)]
    private $password;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $wins = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $losses = 0;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'player1')]
    private Collection $games;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'winner')]
    private Collection $gameWinner;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $requests = null;

    public function __construct()
    {
        $this->games = new ArrayCollection();
        $this->gameWinner = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getWins(): ?int
    {
        return $this->wins;
    }

    public function setWins(?int $wins): static
    {
        $this->wins = $wins;

        return $this;
    }

    public function addWins(): void
    {
        $this->setWins(
            $this->getWins() + 1
        );
    }

    public function getLosses(): ?int
    {
        return $this->losses;
    }


    public function setLosses(?int $losses): static
    {
        $this->losses = $losses;

        return $this;
    }

    public function addLoses(): void
    {
        $this->setLosses(
            $this->getLosses() + 1
        );
    }

    public function getRoles(): array
    {
        // Return an array of roles assigned to the user
        return ['ROLE_USER']; // You can customize this based on your application's needs
    }

    public function getUserIdentifier(): string
    {
        // Return the unique identifier for the user (usually username or email)
        return $this->username; // Assuming username is unique
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setPlayer1($this);
        }

        return $this;
    }

    public function removeGame(Game $game): static
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getPlayer1() === $this) {
                $game->setPlayer1(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGameWinner(): Collection
    {
        return $this->gameWinner;
    }

    public function addGameWinner(Game $gameWinner): static
    {
        if (!$this->gameWinner->contains($gameWinner)) {
            $this->gameWinner->add($gameWinner);
            $gameWinner->setWinner($this);
        }

        return $this;
    }

    public function removeGamesAsPlayer2(Game $gameWinner): static
    {
        if ($this->gameWinner->removeElement($gameWinner)) {
            if ($gameWinner->getWinner() === $this) {            // set the owning side to null
                $gameWinner->setWinner(null);
            }
        }

        return $this;
    }

    public function getRequests(): ?array
    {
        return $this->requests;
    }

    public function setRequests(?array $requests): static
    {
        $this->requests = $requests;

        return $this;
    }

    public function addRequest(string $sender, string $reciver): static
    {
        $this->requests[] = [
            'accept' => null, // Initially set to null
            'opponent' => $sender,
            'reciver' => $reciver,
            'gameId'=>null
        ];
        return $this;
    }

    public function acceptRequest(string $opponent): static
    {
        foreach ($this->requests as &$request) {
            if ($request['opponent'] === $opponent) {
                $request['accept'] = true; // Mark as accepted
                break;
            }
        }
        return $this;
    }

    public function declineRequest(string $opponent): static
    {
        foreach ($this->requests as &$request) {
            if ($request['opponent'] === $opponent) {
                $request['accept'] = false; // Mark as declined
                break;
            }
        }
        return $this;
    }


    public function removeRequest(string $opponent): static
    {
        foreach ($this->requests as $key => $request) {
            if ($request['opponent'] === $opponent) {
                unset($this->requests[$key]); // Remove the request from the array
                $this->requests = array_values($this->requests); // Reindex the array
                break;
            }
        }
        return $this;
    }

    public function getAcceptedRequests(string $opponent): array
    {
        if ($this->requests !== null) {
            return array_filter($this->requests, function ($request) use ($opponent) {
                return $request['opponent'] === $opponent && $request['accept'] === true;
            });
        }else{
            return [];
        }
    }
}
