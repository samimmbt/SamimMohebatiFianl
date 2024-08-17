<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Move;
use App\Entity\User;
use App\Form\UserSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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

    #[Route('/Tic/Tac/Toe', name: 'game_page')]
    public function game(Request $request, AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        $game = $request->query->get('gameId', null);
        
        $users = [];
        $error = $authenticationUtils->getLastAuthenticationError();

        $logger->info("Game info:", [
            'game' => $game,
            'board' => $board,
            'error' => $error,
            'users' => $users,
        ]);

        return $this->redirectToRoute('game_page', [
            'game' => $game,
            'board' => $board,
            'error' => $error,
            'users' => $users,
        ]);
    }

    #[Route('/start', name: 'game_start', methods: ['POST'])]
    public function start(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        // Initialize game variable
        $board = $this->board;
        $position = $request->request->get('position');

        if ($request->isMethod('POST') && $position !== null) {
            if ($position !== null) {
                $board[$position] = 'X'; // Example: Mark the position with 'X'

            }
            if ($board) {
                $board = json_encode($board, true);
            }
        }
        $error = $authenticationUtils->getLastAuthenticationError();
        return $this->redirectToRoute('game_page', ['game' => false, 'board' => $board, 'error' => $error]);
    }

    #[Route('/game/{userId}', name: 'start_game')]
    public function startGame(int $userId): Response
    {
        // Logic to start the game with the selected user
        $user = $this->entityManager->getRepository(User::class)->find($userId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        
        
        // Create a new Game entity and persist it
        $game = new Game();
        $game->setPlayer1Id($this->getUser());
        $game->setPlayer2Id($user);
        $game->setBoard(array_fill(0, 9, null));
        $game->setStatus('in_progress');
        $game->setCreatedAt(new \DateTime());
        // Randomly assign the starting player
        $currentTurn = rand(0, 1) ? 'player1' : 'player2';
        $game->setCurrentTurn($currentTurn);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return $this->redirectToRoute('game_page', ['gameId' => $game->getId()]);
    }

    #[Route('/move/{gameId}', name: 'make_move', methods: ['POST'])]
    public function makeMove(Request $request, $gameId): Response
    {
        $game = $this->entityManager->getRepository(Game::class)->find($gameId);
        if (!$game || $game->getStatus() !== 'in_progress') {
            throw $this->createNotFoundException('Game not found or not in progress');
        }

        $position = $request->request->get('position');
        $user = $this->getUser(); // Assuming the user is authenticated

        // Check if it is the current player's turn
        if (($game->getCurrentTurn() === 'player1' && $game->getPlayer1Id() === $user) ||
            ($game->getCurrentTurn() === 'player2' && $game->getPlayer2Id() === $user)
        ) {

            // Check if the position is valid and not already taken
            if ($game->getBoard()[$position] === null) {
                $game->getBoard()[$position] = $user->getUserIdentifier(); // Mark the board with the player's username
                $move = new Move();
                $move->setGame($game);
                $move->setPlayer($user);
                $move->setPosition($position);

                $this->entityManager->persist($move);
                $this->entityManager->persist($game);
                $this->entityManager->flush();

                // Switch turns
                $game->setCurrentTurn($game->getCurrentTurn() === 'player1' ? 'player2' : 'player1');
            }
        }

        return $this->redirectToRoute('game_page', ['gameId' => $gameId]);
    }

}
