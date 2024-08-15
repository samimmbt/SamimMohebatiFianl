<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Move;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GameController extends AbstractController
{
    public static array $board; // Declare the board as a public property
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->board = array_fill(0, 9, null); // Initialize the board
    }

    #[Route('/index', name: 'app_game')]
    public function index(): Response
    {

        return $this->render('game/index.html.twig', ['game' => null, 'rank' => null]);
    }

    #[Route('/Tic/Tac/Toe', name: 'game_page')]
    public function game(Request $request): Response
    {
        $game = $request->query->get('game', null);
        $board = $request->query->get('board', null);

        if ($board) {
            $board = json_decode($board, true);
        }

        return $this->render('game/start.html.twig', ['game' => $game, 'board' => $board]);
    }

    #[Route('/start', name: 'game_start', methods: ['POST'])]
    public function start(Request $request): Response
    {
        // Initialize game variable
        $board = $this->board;
        $game = new Game();
        $user = new User();
        $move = new Move();
        if ($request->isMethod('POST')) {
            // Get the position from the form submission
            $position = $request->request->get('position');

            // Logic to handle the game state update based on the position
            // For example, update the game state in the database or session
            // Update the board with the player's move (you may want to add player logic here)
            if ($position !== null) {
                $board[$position] = 'X'; // Example: Mark the position with 'X'
            }
            if ($board) {
                $board = json_encode($board, true);
            }
            $game->setPlayer1Id($user->getId()); // Set player names as needed
            $game->setCreatedAt(new \DateTime());
            $move->setPosition($position);
            $move->setGame($game);
            $game->addMove($move);

            $this->entityManager->persist($game);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('game_page', ['game' => $game, 'board' => $board]);
    }
    public function supports(Request $request): ?bool
    {
        dump($request->attributes->get('_route')); // Log the route
        dump($request->getMethod()); // Log the request method
        return $request->attributes->get('_route') === 'app_login' && $request->isMethod('POST');
    }
}
