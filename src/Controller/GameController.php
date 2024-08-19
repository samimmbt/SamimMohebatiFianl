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
    public array $board; // Declare the board as a public property
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
                'users' => $users, // Log all users found
            ]);
            $userArray = [];
            foreach ($users as $user) {
                $userArray[] = [
                    'id' => $user->getId(),          // Get user ID
                    'username' => $user->getUsername() // Get username
                ];
                $logger->info('User found: ' . $user->getUsername() . "data:" . $data['username'], ['Data' => $userArray]);
            }

            return $this->redirectToRoute('home', ['users' => $userArray]);
            // return new JsonResponse(['users'=>$users]);
        }

        return $this->render('game/index.html.twig', ['game' => null, 'rank' => null, 'error' => null, 'users' => $users, 'form' => $form->createView()]);
    }


    #[Route('/Tic/Tac/Toe', name: 'game_page')]
    public function game(Request $request, AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        $gameId = $request->query->get('gameId', null);
        $game = $this->entityManager->getRepository(Game::class)->find($gameId);
        $board = $game->getBoard();
        $users = [];
        $error = $authenticationUtils->getLastAuthenticationError();

        $logger->info("Game info:", [
            'game' => $game,
            'board' => $board,
            'error' => $error,
            'users' => $users,
        ]);
        if (!$gameId) {
            return $this->redirectToRoute('game_page', [
                'game' => $game,
                'board' => $board,
                'error' => $error,
                'users' => $users,
            ]);
        }
        return $this->render("game/start.html.twig", [
            'game' => $game,
            'board' => $board,
            'error' => $error,
            'users' => $users,
        ]);
    }

    #[Route('/start/{game}', name: 'start_a_game', methods: ['POST'])]
    public function startAGame(Request $request, AuthenticationUtils $authenticationUtils, $game): Response
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

    #[Route('/game/{userName}', name: 'start_game')]
    public function startGame(string $userName, LoggerInterface $logger): Response //username is opponent id 
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBySomeField(['username' => $userName]);
        if (!$user) {
            throw $this->createNotFoundException('User not found !!!');
        }

        // Check if the user has an active request to play
        $requests = $user->getRequests(); //getting the user requests from database
        $canPlay = false;
        $logger->info("requests: ",['requests'=>$requests]);

        if ($requests != null) {
            foreach ($requests as $request) {
                if ($request['accept'] === true && $request['opponent'] === $this->getUser()->getUserIdentifier()) {
                    $canPlay = true;
                    break;
                }
            }
        }
        $logger->info("can play?" . ($canPlay ? "yes" : "no"));
        if ($canPlay) {
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
            $logger->info("it can play");
            return $this->redirectToRoute('game_page', ['gameId' => $game->getId()]);
        } else{
                    return $this->redirectToRoute('home');
        }
    }

// _______________________________________________________________________________________________________________
    //move logic ----------------------------------------------------------------------------------------------

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
                $move->setCreatedAt(new \DateTime()); // Set the move timestamp

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
