function initPuissance4Game() {
    console.log("Connexion WS prête. Initialisation de Puissance 4.");

    const board = document.getElementById('board');
    const columns = document.querySelectorAll('.column');
    const statusDisplay = document.getElementById('status');
    const resetButton = document.getElementById('reset-button');
    const gameId = 5; // L'ID de Puissance 4 dans la BDD

    if (typeof conn === 'undefined' || conn.readyState !== WebSocket.OPEN) {
        statusDisplay.textContent = "Erreur de connexion critique."; return;
    }

    if (window.heartbeatInterval) clearInterval(window.heartbeatInterval);
    window.heartbeatInterval = setInterval(() => {
        if (conn.readyState === WebSocket.OPEN) {
            conn.send(JSON.stringify({ type: 'heartbeat', gameId: gameId }));
        }
    }, 10000);

    columns.forEach(column => {
        column.addEventListener('click', () => {
            conn.send(JSON.stringify({
                type: 'puissance4',
                action: 'move',
                columnIndex: column.dataset.column
            }));
        });
    });
    
    resetButton.addEventListener('click', () => {
        conn.send(JSON.stringify({ type: 'puissance4', action: 'reset' }));
    });

    const originalOnMessage = conn.onmessage; 
    conn.onmessage = function(e) {
        if (originalOnMessage) originalOnMessage(e);

        const data = JSON.parse(e.data);
        switch(data.type) {
            case 'puissance4_state':
                updateBoardUI(data.state);
                break;
            case 'puissance4_error':
                // Gérer les erreurs (non implémenté ici pour rester simple)
                break;
        }
    };

    function updateBoardUI(state) {
        const slots = document.querySelectorAll('.slot');
        state.board.forEach((player, index) => {
            slots[index].className = 'slot'; // Reset
            if (player) {
                slots[index].classList.add(player);
            }
        });
        statusDisplay.textContent = state.status;
        resetButton.style.display = state.isGameOver ? 'inline-block' : 'none';
    }
    
    conn.send(JSON.stringify({ type: 'puissance4', action: 'join' }));
}

window.onWsOpenCallbacks.push(initPuissance4Game);
