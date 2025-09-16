// public/js/views/Pong.js

export default {
    data() {
        return {
            ws: null,
            status: 'Connexion...',
            scores: [0, 0],
            playerIndex: -1,
            // Propriétés du canvas
            ctx: null,
            canvasWidth: 800,
            canvasHeight: 600,
            paddleWidth: 10,
            paddleHeight: 100,
            ballSize: 10,
        };
    },
    mounted() {
        this.initCanvas();
        this.connectWebSocket();
        window.addEventListener('mousemove', this.handleMouseMove);
    },
    beforeUnmount() {
        if (this.ws) this.ws.close();
        window.removeEventListener('mousemove', this.handleMouseMove);
    },
    methods: {
        initCanvas() {
            const canvas = this.$refs.pongCanvas;
            if (canvas) {
                this.ctx = canvas.getContext('2d');
            }
        },
        connectWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws/`;
            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                this.ws.send(JSON.stringify({ type: 'pong', action: 'join' }));
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'pong_state') {
                    this.status = data.state.status;
                    this.scores = data.state.scores;
                    this.playerIndex = data.playerIndex;
                    this.draw(data.state);
                }
            };
        },
        handleMouseMove(event) {
            if (this.ws && this.ws.readyState === WebSocket.OPEN && this.playerIndex !== -1) {
                const canvasRect = this.$refs.pongCanvas.getBoundingClientRect();
                const mouseY = event.clientY - canvasRect.top;
                
                this.ws.send(JSON.stringify({
                    type: 'pong',
                    action: 'move',
                    y: mouseY
                }));
            }
        },
        draw(state) {
            if (!this.ctx) return;
            const ctx = this.ctx;

            // Fond
            ctx.fillStyle = '#000';
            ctx.fillRect(0, 0, this.canvasWidth, this.canvasHeight);

            // Ligne centrale
            ctx.fillStyle = '#fff';
            ctx.fillRect(this.canvasWidth / 2 - 1, 0, 2, this.canvasHeight);

            // Raquettes
            ctx.fillStyle = (this.playerIndex === 0) ? '#03DAC6' : '#fff'; // Joueur 1
            ctx.fillRect(0, state.paddles[0].y, this.paddleWidth, this.paddleHeight);

            ctx.fillStyle = (this.playerIndex === 1) ? '#03DAC6' : '#fff'; // Joueur 2
            ctx.fillRect(this.canvasWidth - this.paddleWidth, state.paddles[1].y, this.paddleWidth, this.paddleHeight);

            // Balle
            ctx.beginPath();
            ctx.arc(state.ball.x, state.ball.y, this.ballSize, 0, Math.PI * 2);
            ctx.fillStyle = '#BB86FC';
            ctx.fill();
            ctx.closePath();
        }
    },
    template: `
        <div id="pong-game-vue">
            <h1>Pong</h1>
            <p id="status">{{ status }}</p>
            <div class="scores">
                <span>Joueur 1: {{ scores[0] }}</span>
                <span>Joueur 2: {{ scores[1] }}</span>
            </div>
            <canvas ref="pongCanvas" :width="canvasWidth" :height="canvasHeight"></canvas>
            <div class="game-controls">
                <router-link to="/" class="btn btn-secondary">Retour au menu</router-link>
            </div>
        </div>
    `
};
