<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Move;
use App\Entity\User;
use App\Event\GameRequestEvent;
use App\Form\UserSearchType;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class GameController extends AbstractController
{
    public array $board;
    private $entityManager;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->entityManager = $entityManager;
        $this->board = array_fill(0, 9, null); // Initialize the board
        $this->eventDispatcher = $eventDispatcher;
    }

    #[Route('/home', name: 'home')]
    public function home(Request $request, LoggerInterface $logger): Response
    {
        $form = $this->createForm(UserSearchType::class, null, ['attr' => ['id' => 'form']]);
        $form->handleRequest($request);
        $users = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $users = $this->entityManager->getRepository(User::class)->searchUsers($data['username']);
            $logger->info("Form submitted:", [ // Log the search results
                'form' => $data,
                'users' => $users, 
            ]);
            $userArray = [];
            foreach ($users as $user) {
                $userArray[] = [
                    'id' => $user->getId(),          
                    'username' => $user->getUsername() 
                ];
                $logger->info('User found: ' . $user->getUsername() . "data:" . $data['username'], ['Data' => $userArray]);
            }

            return $this->redirectToRoute('home', ['users' => $userArray]);
            // return new JsonResponse(['users'=>$users]);
        }

        return $this->render('game/index.html.twig', ['game' => null, 'rank' => null, 'error' => null, 'users' => $users, 'form' => $form->createView()]);
    }


    #[Route('/Tic/Tac/Toe', name: 'game_page')]
    public function launchGame(Request $request, LoggerInterface $logger): Response
    {
        $opponentUsername = $request->query->get('opponent');
        $currentUser = $this->getUser();

        if ($opponentUsername === null) {
            $logger->info('Redirecting to home due to missing opponent username.');
            return $this->redirectToRoute('home');
        }

        $opponent = $this->entityManager->getRepository(User::class)->findOneByUsername($opponentUsername);
        if ($opponent === null) {
            $logger->error('Opponent with username ' . $opponentUsername . ' not found.');
            throw $this->createNotFoundException('Opponent not found');
        }

        $game = $this->entityManager->getRepository(Game::class)->findGameByPlayers($currentUser, $opponent);

        if ($game === null || $game->getStatus() !== 'in_progress') {
            // If no game is found or the game is not in progress, create a new one
            $game = new Game();
            $game->setBoard(array_fill(0, 9, null)); // Assuming a 3x3 Tic Tac Toe board
            $game->setStatus('in_progress');
            $game->setCreatedAt(new \DateTime());

            // Randomly assign the starting player
            if (rand(0, 1) === 0) {
                $game->setPlayer1($currentUser);
                $game->setPlayer2($opponent);
                $currentTurn = 'player1';
            } else {
                $game->setPlayer1($opponent);
                $game->setPlayer2($currentUser);
                $currentTurn = 'player2';
            }

            $game->setCurrentTurn($currentTurn);

            $this->entityManager->persist($game);
            $this->entityManager->flush();

            $logger->info('New game created between ' . $currentUser->getUserIdentifier() . ' and ' . $opponent->getUsername());
        }
        $playerRole = ($game->getPlayer1() === $currentUser) ? 'player1' : 'player2';
        $playerMark = ($game->getPlayer1() === $currentUser) ? 'X' : 'O';
        return $this->render("game/start.html.twig", [
            'playerRole' => $playerRole,
            'playerMark' => $playerMark,
            'gameId' => $game->getId(),
            'game' => $game,
            'board' => $game->getBoard(),
            'currentTurn' => $game->getCurrentTurn()
        ]);
    }
    // _______________________________________________________________________________________________________________
    //move logic ----------------------------------------------------------------------------------------------

    #[Route('/move/{gameId}', name: 'make_move', methods: ['POST'])]
    public function makeMove(Request $request, $gameId, LoggerInterface $logger): Response
    {
        $game = $this->entityManager->getRepository(Game::class)->find($gameId);
        if (!$game || $game->getStatus() !== 'in_progress') {
            return $this->json(['success' => false, 'message' => 'Game not found or not in progress']);
        }

        $data = json_decode($request->getContent(), true);
        $position = $data['position'];
        $user = $this->getUser(); 
        $playerMark = $game->getCurrentTurn() === 'player1' ? 'X' : 'O';

        if (($game->getCurrentTurn() === 'player1' && $game->getPlayer1() === $user ||
            $game->getCurrentTurn() === 'player2' && $game->getPlayer2() === $user) && $position !== null) {
            // Check if the position is valid and not already taken
            if ($game->getBoard()[$position] === null) {
                $logger->info($playerMark . " user can move to " . $position);
                $board = $game->getBoard();
                $board[$position] = $playerMark;
                $game->setBoard($board); // Update the board

                $move = new Move();
                $move->setGame($game);
                $move->setPlayer($user);
                $move->setPosition($position);
                $move->setCreatedAt(new \DateTime()); // Set the move timestamp

                $game->setCurrentTurn($game->getCurrentTurn() === 'player1' ? 'player2' : 'player1'); // Switch turns

                $this->entityManager->persist($move);
                $this->entityManager->persist($game);
                $this->entityManager->flush();

                return $this->json([
                    'success' => true,
                    'playerMark' => $playerMark,
                    'message' => 'Move made successfully',
                    'board' => $game->getBoard() // Return the updated board
                ]);
            } else {
                return $this->json(['success' => false, 'message' => 'Position already taken']);
            }
        } else {
            return $this->json(['success' => false, 'message' => 'Not your turn']);
        }
    }
}
