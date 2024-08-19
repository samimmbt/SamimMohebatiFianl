<?php

namespace App\EventListener;

use App\Event\GameRequestEvent;
use Psr\Log\LoggerInterface;

final class GameRequestListener
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function onGameRequest(GameRequestEvent $event): void
    {
        $user = $event->getUser();
        $opponent = $event->getOpponent();
        $action = $event->getAction();
        $this->logger->info("User {$user->getUsername()} performed action '{$action}' on request to {$opponent}");
    }
}