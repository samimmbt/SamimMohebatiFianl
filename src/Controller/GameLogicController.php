<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Move;
use App\Entity\User;
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

class GameLogicController extends AbstractController
{
    /*
    * Checks for a winner every 20 sec
    */
    #[Route('/check-winner/{id}', name: 'game_check_winner', methods: ['POST'])]
    public function checkWinner(int $id, EntityManagerInterface $entityManager): Response
    {
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game || $game->getStatus() !== 'in_progress') {
            return $this->json(['message' => 'Game not found or already finished', 'success' => false]);
        }

        // Assuming checkForWinner is a method that returns either 'player1', 'player2', or null
        $winner = $this->checkForWinner($game->getBoard());

        if ($winner !== null) {
            $game->setStatus('finished');
            if ($winner === 'player1') {
                $game->setWinner($game->getPlayer1());
                $game->getPlayer1()->addWins();
                $game->getPlayer2()->addLoses();
            } else if ($winner === 'player2') {
                $game->setWinner($game->getPlayer2());
                $game->getPlayer2()->addWins();
                $game->getPlayer1()->addLoses();
            }

            $entityManager->flush();

            return $this->json(['message' => "{$winner} wins!", 'success' => true]);
        }

        return $this->json(['message' => 'No winner yet', 'success' => false]);
    }

    private function checkForWinner(array $board): ?string {
        $boardSize = count($board); // Assuming a square board
    
        // Initialize arrays to store diagonal values
        $mainDiagonal = [];
        $secondaryDiagonal = [];
    
        for ($i = 0; $i < $boardSize; $i++) {
            $currentRow = $board[$i];
            $currentColumn = array_column($board, $i);
    
            // Check if all elements in the current row are the same
            if ($this->allElementsMatch($currentRow)) {
                return $this->determinePlayer($currentRow[0]);
            }
    
            // Check if all elements in the current column are the same
            if ($this->allElementsMatch($currentColumn)) {
                return $this->determinePlayer($currentColumn[0]);
            }
    
            // Collect values for both diagonals
            $mainDiagonal[] = $board[$i][$i];
            $secondaryDiagonal[] = $board[$i][$boardSize - $i - 1];
        }
    
        // Check main diagonal for a winner
        if ($this->allElementsMatch($mainDiagonal)) {
            return $this->determinePlayer($mainDiagonal[0]);
        }
    
        // Check secondary diagonal for a winner
        if ($this->allElementsMatch($secondaryDiagonal)) {
            return $this->determinePlayer($secondaryDiagonal[0]);
        }
    
        return null; // No winner yet
    }
    
    /*
     * a helper function to check if all elements in an array are identical and not empty.
     */
    private function allElementsMatch(array $elements): bool {
        if (empty($elements[0])) {
            return false;
        }
        return count(array_unique($elements)) === 1;
    }
    
    /*
     * see which player based on the token ('X' or 'O').
     */
    private function determinePlayer(string $token): ?string {
        return $token === 'X' ? 'player1' : 'player2';
    }

}