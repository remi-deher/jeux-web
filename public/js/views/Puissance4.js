// public/js/views/Puissance4.js

export default {
    data() {
        return {
            board: Array(6).fill(null).map(() => Array(7).fill(null)),
            status: 'Connexion au jeu...',
            isGameOver: false,
            playerColor: null, // <-- AJOUT: pour stocker 'red' ou 'yellow'
            ws: null
        };
    },
    mounted() {
        this.connectWebSocket();
    },
    beforeUnmount() {
        if (this.ws) this.ws.close();
    },
    methods: {
        connectWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws/`;
            
            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                this.ws.send(JSON.stringify({ type: 'puissance4', action: 'join' }));
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'puissance4_state') {
                    this.updateUI(data.state);
                }
            };
        },
        handleColumnClick(colIndex) {
            if (this.ws.readyState === WebSocket.OPEN && !this.isGameOver) {
                this.ws.send(JSON.stringify({
                    type: 'puissance4',
                    action: 'move',
                    columnIndex: colIndex
                }));
            }
        },
        resetGame() {
            if (this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({ type: 'puissance4', action: 'reset' }));
            }
        },
        updateUI(state) {
            this.board = state.board;
            this.status = state.status;
            this.isGameOver = state.isGameOver;
            this.playerColor = state.playerColor; // <-- AJOUT: mise à jour de la couleur
        }
    },
    template: `
        <div id="puissance4-game-vue">
            <h1>Puissance 4</h1>

            <div class="player-info" v-if="playerColor">
                Vous jouez avec les jetons 
                <span class="p4-token-indicator" :class="playerColor"></span>
                <span :class="playerColor + '-text'">{{ playerColor === 'red' ? 'Rouges' : 'Jaunes' }}</span>
            </div>
            <p id="status">{{ status }}</p>
            <div id="p4-board-container">
                <div id="p4-board">
                    <div v-for="(col, colIndex) in 7" :key="colIndex" 
                         class="p4-col" @click="handleColumnClick(colIndex)">
                        
                        <div v-for="(row, rowIndex) in 6" :key="rowIndex" class="p4-cell">
                            <div class="p4-token" :class="board[rowIndex] ? board[rowIndex][colIndex] : null"></div>
                        </div>

                    </div>
                </div>
            </div>
            <div class="game-controls">
                <button class="btn btn-primary" v-if="isGameOver" @click="resetGame">Rejouer</button>
                <router-link to="/" class="btn btn-secondary">Retour au menu</router-link>
            </div>
        </div>
    `
};
