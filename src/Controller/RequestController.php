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
    public array $board;
    private $entityManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->board = array_fill(0, 9, null); // Init the board
        $this->eventDispatcher = $eventDispatcher;
    }
    //dto 
    #[Route('/request/send/{username}', name: 'send_request')] //updated 
    public function sendRequest(String $username): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField(['username' => $username]);
        if (!$user) {
            throw $this->createNotFoundException('User is not here');
        }

        $user->addRequest($this->getUser()->getUserIdentifier(), $user->getUserIdentifier());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Dispatch the event for sending the request
        $this->eventDispatcher->dispatch(new GameRequestEvent($user, $user->getUsername(), 'send'));

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

    #[Route('/request/acceptedRequests', name: 'get_accepted_requests', methods: ['POST'])] //for geting list of accepted requests
    public function getAcceptedRequests(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        
        $users = $this->entityManager->getRepository(User::class)->findAll();// Fetch all users
        $acceptedRequests = [];

        
        foreach ($users as $otherUser) {// Check each user for accepted requests where the current user is the opponent
            $requests = $otherUser->getAcceptedRequests($user->getUserIdentifier());
            if (!empty($requests)) {
                $acceptedRequests[] = [
                    'opponent' => $otherUser->getUserIdentifier(),
                    'requests' => $requests,
                ];
            }
        }
        $this->eventDispatcher->dispatch(new GameRequestEvent($user, $user->getUserIdentifier(), 'accepted_requests'));
        return $this->json($acceptedRequests);
    }

    #[Route('/request/accept/{opponent}', name: 'accept_request')] //for accepting
    public function acceptRequest(string $opponent): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField($this->getUser()->getUserIdentifier());
        $user->acceptRequest($opponent);
        // $user->removeRequest($opponent); //not useable
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new GameRequestEvent($user, $opponent, 'accept'));

        return $this->json(['success' => 'Request accepted']);
    }

    #[Route('/request/reject/{opponent}', name: 'reject_request')] //for rejecting
    public function rejectRequest(string $opponent): Response
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField($this->getUser()->getUserIdentifier());
        $user->declineRequest($opponent);
        $user->removeRequest($opponent);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new GameRequestEvent($user, $opponent, 'reject'));

        return $this->json(['success' => 'Request accepted']);
    }
}
