// public/js/views/Morpion.js

export default {
    data() {
        return {
            board: Array(9).fill(null),
            status: 'Connexion au jeu...',
            isGameOver: false,
            playerSymbol: null, // <-- AJOUT: pour stocker 'x' ou 'o'
            ws: null
        };
    },
    mounted() {
        this.connectWebSocket();
    },
    beforeUnmount() {
        if (this.ws) {
            this.ws.close();
        }
    },
    methods: {
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
            this.playerSymbol = state.playerSymbol; // <-- AJOUT: mise à jour du symbole
        }
    },
    template: `
        <div id="morpion-game-vue">
            <h1>Morpion en temps réel</h1>
            
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
