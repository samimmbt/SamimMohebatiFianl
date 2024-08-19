<?php

namespace App\Controller;

use App\Entity\User;
use App\Event\GameRequestEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Doctrine\ORM\EntityManagerInterface;


class RequestController extends AbstractController
{
    public array $board; // Declare the board as a public property
    private $entityManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->board = array_fill(0, 9, null); // Initialize the board
        $this->eventDispatcher = $eventDispatcher;

    }
    #[Route('/request/send/{opponent}', name: 'send_request')]
    public function sendRequest(string $opponent): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField(['username' => $opponent]);

        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // Logic to send the request
        $user->addRequest($this->getUser()->getUserIdentifier(), $user->getUserIdentifier());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Dispatch the event for sending the request
        $this->eventDispatcher->dispatch(new GameRequestEvent($user, $opponent, 'send'));

        return $this->json(['success' => 'Request sent']);
    }

    #[Route('/request', name: 'get_request',  methods: ['GET'])] //request for games
    public function searchForRequest(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField($this->getUser()->getUserIdentifier());
        $requests = $user->getRequests();
        return new JsonResponse($requests);
    }

    // src/Controller/GameController.php

#[Route('/request/acceptedRequests', name: 'get_accepted_requests', methods: ['POST'])]
public function getAcceptedRequests(Request $request): JsonResponse
{
    $user = $this->getUser();

    if (!$user instanceof User) {
        return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
    }

    // Fetch all users
    $users = $this->entityManager->getRepository(User::class)->findAll();
    $acceptedRequests = [];

    // Check each user for accepted requests where the current user is the opponent
    foreach ($users as $otherUser) {
        $requests = $otherUser->getAcceptedRequests($user->getUserIdentifier());
        if (!empty($requests)) {
            $acceptedRequests[] = [
                'opponent' => $otherUser->getUserIdentifier(),
                'requests' => $requests,
            ];
        }
    }

    return $this->json($acceptedRequests);
}

    #[Route('/request/accept/{opponent}', name: 'accept_request')] //for accepting
    public function acceptRequest(string $opponent): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField($this->getUser()->getUserIdentifier());
        $user->acceptRequest($opponent);
        // $user->removeRequest($opponent);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['success' => 'Request accepted']);
    }

    #[Route('/request/reject/{opponent}', name: 'reject_request')] //for rejecting
    public function rejectRequest(string $opponent): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField($this->getUser()->getUserIdentifier());
        $user->declineRequest($opponent);
        // $user->removeRequest($opponent);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json(['success' => 'Request accepted']);
    }
}
