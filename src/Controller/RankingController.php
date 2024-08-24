<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\UserRepository;

class RankingController extends AbstractController
{
    #[Route('/ranking', name: 'app_ranking')]
    public function index(UserRepository $userRepository): JsonResponse
    {
        $users = $userRepository->findTopUsersByWins(10); // Fetch top 10 users by wins and return it

        $rankingData = array_map(function ($user) {
            return [
                'number' => $user->getId(),
                'name' => $user->getUsername(),
                'wins' => $user->getWins(),
            ];
        }, $users);

        return $this->json(['success' => true, 'data' => $rankingData]);
    }
}