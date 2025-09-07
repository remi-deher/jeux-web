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
            
            ships: [
                { name: 'porte-avions', size: 5, placed: false, x: 0, y: 0, orientation: 'horizontal', isValid: true },
                { name: 'croiseur', size: 4, placed: false, x: 0, y: 1, orientation: 'horizontal', isValid: true },
                { name: 'contre-torpilleur', size: 3, placed: false, x: 0, y: 2, orientation: 'horizontal', isValid: true },
                { name: 'sous-marin', size: 3, placed: false, x: 0, y: 3, orientation: 'horizontal', isValid: true },
                { name: 'torpilleur', size: 2, placed: false, x: 0, y: 4, orientation: 'horizontal', isValid: true }
            ],
            
            draggedShipIndex: null,
            draggedOffsetX: 0,
            draggedOffsetY: 0,
        };
    },
    computed: {
        allShipsPlacedAndValid() {
            return this.ships.every(ship => ship.placed && ship.isValid);
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
            // Uniquement mettre à jour les plateaux si on n'est pas en phase de placement
            // pour ne pas écraser le travail de l'utilisateur.
            if(this.phase !== 'placement' && state.myBoard && state.myBoard.length > 0) {
                this.myBoard = state.myBoard;
            }
            if (state.opponentBoard && state.opponentBoard.length > 0) {
                this.opponentBoard = state.opponentBoard;
            }
        },

        getShipCoords(ship) {
            const coords = [];
            for (let i = 0; i < ship.size; i++) {
                const x = ship.orientation === 'horizontal' ? ship.x + i : ship.x;
                const y = ship.orientation === 'vertical' ? ship.y + i : ship.y;
                coords.push({ x, y });
            }
            return coords;
        },

        validateAllShips() {
            const occupiedCoords = new Set();
            this.ships.forEach(ship => {
                if (!ship.placed) return;

                let isValid = true;
                const currentShipCoords = [];

                this.getShipCoords(ship).forEach(coord => {
                    const key = `${coord.x},${coord.y}`;
                    if (coord.x < 0 || coord.x >= 10 || coord.y < 0 || coord.y >= 10 || occupiedCoords.has(key)) {
                        isValid = false;
                    }
                    currentShipCoords.push(key);
                });
                
                ship.isValid = isValid;
                if (isValid) {
                    currentShipCoords.forEach(key => occupiedCoords.add(key));
                }
            });
        },

        placeShipFromList(shipIndex) {
            if (this.ships[shipIndex].placed) return;
            const ship = this.ships[shipIndex];
            ship.placed = true;
            this.validateAllShips();
        },
        
        rotatePlacedShip(shipIndex) {
            const ship = this.ships[shipIndex];
            ship.orientation = (ship.orientation === 'horizontal') ? 'vertical' : 'horizontal';
            this.validateAllShips();
        },

        startDrag(event, shipIndex) {
            event.preventDefault();
            this.draggedShipIndex = shipIndex;
            const ship = this.ships[shipIndex];
            
            const isTouchEvent = event.type.includes('touch');
            const clientX = isTouchEvent ? event.targetTouches[0].clientX : event.clientX;
            const clientY = isTouchEvent ? event.targetTouches[0].clientY : event.clientY;
            const boardRect = event.currentTarget.closest('.bn-board').getBoundingClientRect();
            const cellWidth = boardRect.width / 10;
            
            this.draggedOffsetX = Math.floor((clientX - boardRect.left) / cellWidth) - ship.x;
            this.draggedOffsetY = Math.floor((clientY - boardRect.top) / cellWidth) - ship.y;

            window.addEventListener('mousemove', this.doDrag);
            window.addEventListener('mouseup', this.stopDrag);
            window.addEventListener('touchmove', this.doDrag, { passive: false });
            window.addEventListener('touchend', this.stopDrag);
        },
        doDrag(event) {
            if (this.draggedShipIndex === null) return;
            event.preventDefault();

            const isTouchEvent = event.type.includes('touch');
            const clientX = isTouchEvent ? event.targetTouches[0].clientX : event.clientX;
            const clientY = isTouchEvent ? event.targetTouches[0].clientY : event.clientY;
            
            const boardRect = this.$el.querySelector('.bn-board').getBoundingClientRect();
            const cellWidth = boardRect.width / 10;
            
            const newX = Math.round((clientX - boardRect.left) / cellWidth) - this.draggedOffsetX;
            const newY = Math.round((clientY - boardRect.top) / cellWidth) - this.draggedOffsetY;
            
            const ship = this.ships[this.draggedShipIndex];
            if (ship.x !== newX || ship.y !== newY) {
                ship.x = newX;
                ship.y = newY;
                this.validateAllShips();
            }
        },
        stopDrag() {
            this.draggedShipIndex = null;
            window.removeEventListener('mousemove', this.doDrag);
            window.removeEventListener('mouseup', this.stopDrag);
            window.removeEventListener('touchmove', this.doDrag);
            window.removeEventListener('touchend', this.stopDrag);
        },
        resetPlacement() {
            this.ships.forEach(ship => {
                ship.placed = false;
                ship.isValid = true;
            });
        },
        confirmPlacement() {
            if (!this.allShipsPlacedAndValid) {
                alert('Veuillez placer tous vos navires correctement.');
                return;
            }
            const finalPlacements = this.ships.map(ship => ({
                name: ship.name,
                coords: this.getShipCoords(ship)
            }));
            
            this.ws.send(JSON.stringify({
                type: 'bataille_navale',
                action: 'place_ships',
                ships: finalPlacements
            }));
            this.status = "En attente de l'adversaire...";
        },
        fireShot(y, x) {
            if (this.phase !== 'battle' || !this.isMyTurn || this.opponentBoard[y][x] === 'hit' || this.opponentBoard[y][x] === 'miss') return;
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
                <h2>Placez et déplacez vos navires</h2>
                <div class="game-controls placement-controls">
                    <div class="ship-list">
                        <div v-for="(ship, index) in ships" :key="ship.name"
                             class="ship-item" :class="{ 'placed': ship.placed }"
                             @click="placeShipFromList(index)">
                             {{ ship.name }} ({{ ship.size }})
                        </div>
                    </div>
                    <button class="btn btn-secondary" @click="resetPlacement">Réinitialiser</button>
                    <button class="btn btn-primary" @click="confirmPlacement" :disabled="!allShipsPlacedAndValid">Valider le placement</button>
                </div>
                <div class="boards-container">
                    <div>
                        <h3>Mon Plateau</h3>
                        <div class="bn-board placement-board">
                             <div v-for="i in 100" class="bn-cell water"></div>
                            <template v-for="(ship, index) in ships">
                                <div v-if="ship.placed"
                                     class="placed-ship"
                                     :class="[ship.orientation, { 'invalid': !ship.isValid }]"
                                     :style="{ 
                                         top: ship.y * 10 + '%', 
                                         left: ship.x * 10 + '%',
                                         width: (ship.orientation === 'horizontal' ? ship.size * 10 : 10) + '%',
                                         height: (ship.orientation === 'vertical' ? ship.size * 10 : 10) + '%'
                                     }"
                                     @click.stop="rotatePlacedShip(index)"
                                     @mousedown.prevent="startDrag($event, index)"
                                     @touchstart.prevent="startDrag($event, index)">
                                     <span class="ship-name-on-grid">{{ ship.name }}</span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="phase === 'battle' || phase === 'gameover'">
                 <div class="boards-container">
                    <div v-if="isMyTurn">
                        <h3>Plateau Adverse</h3>
                        <div class="bn-board opponent my-turn">
                             <div v-for="(row, y) in opponentBoard" class="bn-row">
                                <div v-for="(cell, x) in row" class="bn-cell" :class="cell" @click="fireShot(y, x)"></div>
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        <h3>Mon Plateau</h3>
                        <div class="bn-board">
                             <div v-for="row in myBoard" class="bn-row">
                                <div v-for="cell in row" class="bn-cell" :class="cell"></div>
                            </div>
                        </div>
                    </div>
                </div>
                 <div class="game-controls">
                    <router-link to="/" class="btn btn-secondary">Retour au menu</router-link>
                </div>
            </div>
            </div>
    `
};
