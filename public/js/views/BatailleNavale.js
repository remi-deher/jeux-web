// public/js/views/BatailleNavale.js

export default {
    data() {
        return {
            ws: null,
            phase: 'waiting',
            isMyTurn: false,
            status: 'Connexion...',
            myBoard: Array(10).fill(null).map(() => Array(10).fill('water')),
            opponentBoard: Array(10).fill(null).map(() => Array(10).fill('water')),
            
            // Pour la phase de placement
            shipsToPlace: [
                { name: 'porte-avions', size: 5, placed: false },
                { name: 'croiseur', size: 4, placed: false },
                { name: 'contre-torpilleur', size: 3, placed: false },
                { name: 'sous-marin', size: 3, placed: false },
                { name: 'torpilleur', size: 2, placed: false }
            ],
            selectedShipIndex: 0,
            orientation: 'horizontal', // 'horizontal' or 'vertical'
            placements: []
        };
    },
    computed: {
        allShipsPlaced() {
            return this.shipsToPlace.every(ship => ship.placed);
        }
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
                this.ws.send(JSON.stringify({ type: 'bataille_navale', action: 'join' }));
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'bataille_navale_state') {
                    this.updateUI(data.state);
                }
            };
        },
        updateUI(state) {
            this.phase = state.phase;
            this.isMyTurn = state.isMyTurn;
            this.status = state.status;
            if (state.myBoard && state.myBoard.length > 0) this.myBoard = state.myBoard;
            if (state.opponentBoard && state.opponentBoard.length > 0) this.opponentBoard = state.opponentBoard;
        },
        // --- Méthodes pour la phase de placement ---
        selectShip(index) {
            if (this.shipsToPlace[index].placed) return;
            this.selectedShipIndex = index;
        },
        toggleOrientation() {
            this.orientation = (this.orientation === 'horizontal') ? 'vertical' : 'horizontal';
        },
        placeShip(y, x) {
            if (this.phase !== 'placement' || this.shipsToPlace[this.selectedShipIndex].placed) return;

            const ship = this.shipsToPlace[this.selectedShipIndex];
            const newPlacement = { name: ship.name, coords: [] };
            
            for (let i = 0; i < ship.size; i++) {
                const newY = this.orientation === 'vertical' ? y + i : y;
                const newX = this.orientation === 'horizontal' ? x + i : x;

                // Vérification des limites et des chevauchements
                if (newY >= 10 || newX >= 10 || this.myBoard[newY][newX] === 'ship') {
                    alert('Placement invalide !');
                    return;
                }
                newPlacement.coords.push({ y: newY, x: newX });
            }

            // Mettre à jour le plateau localement
            newPlacement.coords.forEach(coord => {
                this.myBoard[coord.y][coord.x] = 'ship';
            });

            ship.placed = true;
            this.placements.push(newPlacement);
            
            // Sélectionner le prochain navire non placé
            const nextShipIndex = this.shipsToPlace.findIndex(s => !s.placed);
            if (nextShipIndex !== -1) {
                this.selectedShipIndex = nextShipIndex;
            }
        },
        confirmPlacement() {
            if (!this.allShipsPlaced) {
                alert('Veuillez placer tous vos navires.');
                return;
            }
            this.ws.send(JSON.stringify({
                type: 'bataille_navale',
                action: 'place_ships',
                ships: this.placements
            }));
            // ▼▼▼ LIGNE CORRIGÉE ▼▼▼
            this.status = "En attente de l'adversaire...";
        },

        // --- Méthode pour la phase de bataille ---
        fireShot(y, x) {
            if (this.phase !== 'battle' || !this.isMyTurn) return;
            if (this.opponentBoard[y][x] === 'hit' || this.opponentBoard[y][x] === 'miss') return;

            this.ws.send(JSON.stringify({
                type: 'bataille_navale',
                action: 'fire_shot',
                coords: { y, x }
            }));
        }
    },
    template: `
        <div id="bataille-navale-game">
            <h1>Bataille Navale</h1>
            <p id="status" :class="{ 'my-turn': isMyTurn }">{{ status }}</p>

            <div v-if="phase === 'placement'">
                <h2>Placez vos navires</h2>
                <div class="placement-controls">
                    <div class="ship-list">
                        <div v-for="(ship, index) in shipsToPlace"
                             :key="ship.name"
                             class="ship-item"
                             :class="{ 'selected': index === selectedShipIndex && !ship.placed, 'placed': ship.placed }"
                             @click="selectShip(index)">
                             {{ ship.name }} ({{ ship.size }})
                        </div>
                    </div>
                    <button @click="toggleOrientation">Orientation: {{ orientation }}</button>
                    <button @click="confirmPlacement" :disabled="!allShipsPlaced">Valider le placement</button>
                </div>
                <div class="boards-container">
                    <div>
                        <h3>Mon Plateau</h3>
                        <div class="bn-board">
                            <div v-for="(row, y) in myBoard" class="bn-row">
                                <div v-for="(cell, x) in row" 
                                     class="bn-cell"
                                     :class="cell"
                                     @click="placeShip(y, x)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="phase === 'battle' || phase === 'gameover'">
                 <div class="boards-container">
                    <div>
                        <h3>Mon Plateau</h3>
                        <div class="bn-board">
                             <div v-for="row in myBoard" class="bn-row">
                                <div v-for="cell in row" class="bn-cell" :class="cell"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3>Plateau Adverse</h3>
                        <div class="bn-board opponent">
                             <div v-for="(row, y) in opponentBoard" class="bn-row">
                                <div v-for="(cell, x) in row" 
                                     class="bn-cell" 
                                     :class="cell"
                                     @click="fireShot(y, x)">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    `
};
