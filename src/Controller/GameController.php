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
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class GameController extends AbstractController
{
    public array $board; // Declare the board as a public property
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->board = array_fill(0, 9, null); // Initialize the board
    }

    #[Route('/index', name: 'app_game')]
    public function index(): Response
    {

        return $this->render('game/index.html.twig', ['game' => null, 'rank' => null, 'error' => null]);
    }

    #[Route('/Tic/Tac/Toe', name: 'game_page')]
    public function game(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $game = $request->query->get('game', null);
        $board = $request->query->get('board', null);

        if ($board) {
            $board = json_decode($board, true);
        }
        $error = $authenticationUtils->getLastAuthenticationError();

        return $this->render('game/start.html.twig', ['game' => $game, 'board' => $board, 'error' => $error]);
    }

    #[Route('/start', name: 'game_start', methods: ['POST'])]
    public function start(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Initialize game variable
        $board = $this->board;
        $game = new Game();
        $user = new User();
        $move = new Move();
        $position = $request->request->get('position');
        if ($request->isMethod('POST') && $position !== null) {
            // Get the position from the form submission

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
        $error = $authenticationUtils->getLastAuthenticationError();
        return $this->redirectToRoute('game_page', ['game' => $game, 'board' => $board, 'error' => $error]);
    }
    public function supports(Request $request): ?bool
    {
        dump($request->attributes->get('_route')); // Log the route
        dump($request->getMethod()); // Log the request method
        return $request->attributes->get('_route') === 'app_login' && $request->isMethod('POST');
    }
}
