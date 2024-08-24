<?php

namespace App\Controller;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GameLogicController extends AbstractController
{
    #[Route('/check-winner/{id}', name: 'game_check_winner', methods: ['POST'])] //Checks for a winner every 20 sec
    public function checkWinner(int $id, EntityManagerInterface $entityManager, LoggerInterface $logger): Response
    {
        $game = $entityManager->getRepository(Game::class)->find($id);

        if (!$game || $game->getStatus() !== 'in_progress') {
            return $this->json(['message' => 'Game not found or already finished', 'success' => false]);
        }
        $board = $game->getBoard();
        // $logger->info("Received board as string", ['board' => $board]);

        if (is_string($board)) {
            $board = json_decode($board, true);
            if ($board === null && json_last_error() !== JSON_ERROR_NONE) {
                $logger->error("JSON decode error", ['error' => json_last_error_msg()]);
                throw new \Exception("Error decoding JSON");
            }
        }

        // $logger->info("Decoded board to array", $board);

        $winner = $this->checkForWinner($board, $logger);

        if ($winner !== null) {
            $game->setStatus('finished');
            if ($winner === 'player1') { // selecting the winner
                $game->setWinner($game->getPlayer1());
                $game->getPlayer1()->addWins();
                $game->getPlayer2()->addLoses();
            } else if ($winner === 'player2') {
                $game->setWinner($game->getPlayer2());
                $game->getPlayer2()->addWins();
                $game->getPlayer1()->addLoses();
            }
            $entityManager->persist($game);
            $entityManager->flush();

            return $this->json(['message' => "{$winner} wins!", 'success' => true, 'winner' => $winner]);
        }

        return $this->json(['message' => 'No winner yet', 'success' => false]);
    }

    private function checkForWinner(array $board, LoggerInterface $logger): ?string
    {
        $logger->info("Checking for winner with current board state", ['board' => $board]);
        $size = 3; // For a 3x3 board

        $mainDiagonal = [];
        $secondaryDiagonal = [];

        for ($i = 0; $i < $size; $i++) {
            $row = array_slice($board, $i * $size, $size);        // Extract rows and columns from the flat array
            $column = array_column(array_chunk($board, $size), $i);
            if ($this->allElementsMatch($row)) {        // Check rows and columns for a win
                return $this->determinePlayer($row[0]);
            }

            if ($this->allElementsMatch($column)) {
                return $this->determinePlayer($column[0]);
            }

            $mainDiagonal[] = $board[$i * $size + $i];        // Collect values for both dia somthings
            $secondaryDiagonal[] = $board[$i * $size + ($size - $i - 1)];
        }

        if ($this->allElementsMatch($mainDiagonal)) {       // Check main diagonal for a winner
            return $this->determinePlayer($mainDiagonal[0]);
        }

        if ($this->allElementsMatch($secondaryDiagonal)) {    // Check secondary diagonal for a winner
            return $this->determinePlayer($secondaryDiagonal[0]);
        }

        return null; // No winner yet
    }

    private function allElementsMatch(array $elements): bool //a helper function to check if all elements in an array are identical and not empty.
    {
        if (empty($elements[0])) {
            return false;
        }
        return count(array_unique($elements)) === 1;
    }

    private function determinePlayer(string $token): ?string //see which player based on the token ('X' or 'O').
    {
        return $token === 'X' ? 'player1' : 'player2';
    }
}
