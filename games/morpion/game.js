/**
 * /games/morpion/game.js
 * * Logique côté client pour le jeu du morpion.
 * Ce script dépend de /js/app.js pour la variable de connexion globale 'conn'.
 */

/**
 * Toute la logique du jeu est encapsulée dans cette fonction.
 * Elle ne sera exécutée que lorsque app.js nous dira que la connexion WebSocket est prête.
 */
function initMorpionGame() {
    console.log("Connexion WS prête. Initialisation de la logique du jeu Morpion.");

    // --- Récupération des éléments du DOM ---
    const board = document.getElementById('board');
    const cells = document.querySelectorAll('.cell');
    const statusDisplay = document.getElementById('status');
    const resetButton = document.getElementById('reset-button');
    const gameId = 4; // L'ID du morpion dans votre BDD

    // S'assurer que la variable globale conn existe maintenant
    if (typeof conn === 'undefined' || conn.readyState !== WebSocket.OPEN) {
        console.error("Erreur critique : initMorpionGame a été appelé mais la connexion n'est pas prête.");
        statusDisplay.textContent = "Erreur de connexion critique.";
        return;
    }

    // --- Heartbeat pour le comptage de joueurs sur ce jeu ---
    if (window.heartbeatInterval) clearInterval(window.heartbeatInterval);
    window.heartbeatInterval = setInterval(() => {
        if (conn.readyState === WebSocket.OPEN) {
            // Le heartbeat est un message global, pas spécifique à un jeu
            conn.send(JSON.stringify({ type: 'heartbeat', gameId: gameId }));
        }
    }, 10000); // Toutes les 10 secondes

    // --- Écouteurs d'événements (actions du joueur) ---

    // Clic sur une case du plateau
    board.addEventListener('click', (event) => {
        if (event.target.classList.contains('cell')) {
            const index = event.target.dataset.index;
            // Envoi du message au format attendu par le routeur
            conn.send(JSON.stringify({
                type: 'morpion',      // Le nom du handler
                action: 'move',       // L'action à effectuer
                cellIndex: index    // Les données pour cette action
            }));
        }
    });
    
    // Clic sur le bouton pour rejouer
    resetButton.addEventListener('click', () => {
        conn.send(JSON.stringify({ 
            type: 'morpion',
            action: 'reset'
        }));
    });

    // --- Gestionnaire de messages venant du serveur ---
    
    // On "augmente" le gestionnaire de messages de app.js pour ne pas écraser la logique du chat
    const originalOnMessage = conn.onmessage; 
    conn.onmessage = function(e) {
        if (originalOnMessage) {
            originalOnMessage(e); // Exécute la logique de app.js (chat, etc.)
        }

        const data = JSON.parse(e.data);

        // On ne traite que les messages qui concernent le morpion
        switch(data.type) {
            case 'morpion_state':
                updateBoardUI(data.state);
                break;
            case 'morpion_error':
                // Affiche une erreur temporairement
                const originalStatus = statusDisplay.textContent;
                statusDisplay.textContent = data.message;
                setTimeout(() => {
                    statusDisplay.textContent = originalStatus;
                }, 3000);
                break;
        }
    };

    /**
     * Met à jour l'interface utilisateur (le plateau) en fonction de l'état reçu du serveur.
     * @param {object} state - L'objet d'état du jeu.
     */
    function updateBoardUI(state) {
        cells.forEach((cell, index) => {
            const symbol = state.board[index];
            cell.textContent = symbol ? symbol.toUpperCase() : '';
            cell.className = 'cell'; // Réinitialise les classes
            if (symbol) {
                cell.classList.add(symbol); // Ajoute la classe 'x' ou 'o' pour la couleur
            }
        });

        statusDisplay.textContent = state.status;
        resetButton.style.display = state.isGameOver ? 'inline-block' : 'none';
    }
    
    // --- Initialisation ---
    // On informe le serveur qu'on a rejoint la page du jeu pour recevoir l'état actuel.
    conn.send(JSON.stringify({ 
        type: 'morpion',
        action: 'join' 
    }));
}

/**
 * C'est la seule chose qui s'exécute au chargement initial du script.
 * On enregistre notre fonction d'initialisation dans le tableau global que app.js va gérer.
 * C'est la méthode la plus sûre pour éviter les "race conditions".
 */
window.onWsOpenCallbacks.push(initMorpionGame);
