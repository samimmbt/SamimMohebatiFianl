<?php
namespace App\Event;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

class GameRequestEvent extends Event
{
    public const NAME = 'game.request';

    private User $user;
    private string $opponent;
    private string $action; // 'send', 'accept', or 'reject' ...

    public function __construct(User $user, string $opponent, string $action)
    {
        $this->user = $user;
        $this->opponent = $opponent;
        $this->action = $action;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getOpponent(): string
    {
        return $this->opponent;
    }

    public function getAction(): string
    {
        return $this->action;
    }
}