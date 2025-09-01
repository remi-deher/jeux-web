// public/js/views/Morpion.js

export default {
    data() {
        return {
            board: Array(9).fill(null),
            status: 'Connexion au jeu...',
            isGameOver: false,
            playerSymbol: null,
            ws: null
        };
    },
    // ▼▼▼ SECTION MODIFIÉE ▼▼▼
    mounted() {
        this.connectWebSocket();
        this.setBoardSize(); // On appelle la nouvelle fonction au chargement
        window.addEventListener('resize', this.setBoardSize); // On ajuste si la fenêtre est redimensionnée
    },
    beforeUnmount() {
        if (this.ws) {
            this.ws.close();
        }
        window.removeEventListener('resize', this.setBoardSize); // On nettoie l'écouteur d'événement
    },
    // ▲▲▲ FIN SECTION MODIFIÉE ▲▲▲
    methods: {
        // ▼▼▼ NOUVELLE FONCTION AJOUTÉE ▼▼▼
        setBoardSize() {
            // On s'assure que le code s'exécute côté client
            if (typeof window !== 'undefined') {
                const boardElement = this.$el.querySelector('#board');
                if (boardElement) {
                    // On prend la largeur du conteneur du jeu
                    const containerWidth = boardElement.parentElement.clientWidth;
                    // On calcule la taille de la grille (90% de la largeur, max 400px)
                    const boardSize = Math.min(containerWidth * 0.90, 400);

                    // On applique la taille fixe à la grille
                    boardElement.style.width = `${boardSize}px`;
                    boardElement.style.height = `${boardSize}px`;

                    // On calcule la taille de chaque cellule (1/3 de la grille moins les espaces)
                    const gap = 5; // Correspond au 'gap' dans le CSS
                    const cellSize = (boardSize - 2 * gap) / 3;
                    
                    const cells = boardElement.querySelectorAll('.cell');
                    cells.forEach(cell => {
                        cell.style.width = `${cellSize}px`;
                        cell.style.height = `${cellSize}px`;
                    });
                }
            }
        },
        // ▲▲▲ FIN NOUVELLE FONCTION ▲▲▲
        connectWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws/`;
            
            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                this.ws.send(JSON.stringify({ type: 'morpion', action: 'join' }));
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'morpion_state') {
                    this.updateBoardUI(data.state);
                } else if (data.type === 'morpion_error') {
                    alert(data.message);
                }
            };
        },
        handleCellClick(index) {
            if (this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({
                    type: 'morpion',
                    action: 'move',
                    cellIndex: index
                }));
            }
        },
        resetGame() {
             if (this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({ type: 'morpion', action: 'reset' }));
            }
        },
        updateBoardUI(state) {
            this.board = state.board;
            this.status = state.status;
            this.isGameOver = state.isGameOver;
            this.playerSymbol = state.playerSymbol;
        }
    },
    template: `
        <div id="morpion-game-vue">
            <h1>Morpion</h1>
            
            <div class="player-info" v-if="playerSymbol">
                Vous êtes le joueur <span :class="playerSymbol">{{ playerSymbol.toUpperCase() }}</span>
            </div>

            <p id="status">{{ status }}</p>
            <div id="board">
                <div v-for="(cell, index) in board" 
                     :key="index" 
                     class="cell"
                     :class="cell"
                     @click="handleCellClick(index)">
                     {{ cell ? cell.toUpperCase() : '' }}
                </div>
            </div>
            <div class="game-controls">
                <button class="btn btn-primary" v-if="isGameOver" @click="resetGame">Rejouer</button>
                <router-link to="/" class="btn btn-secondary">Retour au menu</router-link>
            </div>
        </div>
    `
};
