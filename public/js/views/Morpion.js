// public/js/views/Morpion.js

export default {
    data() {
        return {
            board: Array(9).fill(null),
            status: 'Connexion au jeu...',
            isGameOver: false,
            ws: null
        };
    },
    mounted() {
        this.connectWebSocket();
    },
    beforeUnmount() {
        // Nettoyer la connexion quand on quitte la page
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
                console.log("WebSocket connecté pour le Morpion !");
                // Informer le serveur qu'on rejoint la partie
                this.ws.send(JSON.stringify({ type: 'morpion', action: 'join' }));
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'morpion_state') {
                    this.updateBoardUI(data.state);
                } else if (data.type === 'morpion_error') {
                    // Gérer les erreurs
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
        }
    },
    template: `
        <div id="morpion-game-vue">
            <h1>Morpion en temps réel</h1>
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
            <button id="reset-button" v-if="isGameOver" @click="resetGame">Rejouer</button>
            <router-link to="/" class="button-link">Retour au menu</router-link>
        </div>
    `
};
