import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';

// console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
// start.html ______________________________________________________________________
function fetchGameState() {
    fetch(`/game/state/${gameId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            // Update the game board in the UI
            updateBoard(data.board);
            updateCurrentTurn(data.currentTurn);
        })
        .catch(error => console.error('Error fetching game state:', error));
}

function updateBoard(board) {
    // Logic to update the game board UI based on the board array
    for (let i = 0; i < board.length; i++) {
        const cell = document.getElementById(`cell-${i}`); // Assuming you have cells with IDs like cell-0, cell-1, etc.
        if (cell) {
            cell.textContent = board[i] ? board[i] : ''; // Update cell content
        }
    }
}

function updateCurrentTurn(currentTurn) {
    // Logic to update the UI to show whose turn it is
    const turnDisplay = document.getElementById('current-turn');
    turnDisplay.textContent = currentTurn === 'player1' ? 'Player 1\'s Turn' : 'Player 2\'s Turn';
}

// Fetch the game state every 2 seconds
