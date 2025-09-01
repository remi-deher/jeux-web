// public/js/components/Chat.js

export default {
    data() {
        return {
            messages: [],
            newMessage: '',
            ws: null
        };
    },
    mounted() {
        this.connectWebSocket();
    },
    methods: {
        connectWebSocket() {
            const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
            const wsUrl = `${protocol}//${window.location.host}/ws/`;
            
            this.ws = new WebSocket(wsUrl);

            this.ws.onopen = () => {
                console.log("WebSocket connecté pour le chat !");
            };

            this.ws.onmessage = (event) => {
                const data = JSON.parse(event.data);
                if (data.type === 'new_chat_message') {
                    this.messages.push(data);
                    // Fait défiler vers le bas automatiquement
                    this.$nextTick(() => {
                        const container = this.$refs.chatMessages;
                        container.scrollTop = container.scrollHeight;
                    });
                }
                // D'autres types de messages globaux pourraient être gérés ici
            };

            this.ws.onclose = () => {
                console.log("WebSocket déconnecté. Tentative de reconnexion...");
                setTimeout(this.connectWebSocket, 3000); // Reconnexion après 3s
            };

             this.ws.onerror = (err) => {
                console.error("Erreur WebSocket: ", err);
                this.ws.close();
            };
        },
        sendMessage() {
            if (this.newMessage.trim() !== '' && this.ws.readyState === WebSocket.OPEN) {
                this.ws.send(JSON.stringify({
                    type: 'chat_message',
                    message: this.newMessage
                }));
                this.newMessage = '';
            }
        }
    },
    template: `
        <aside id="chat-section">
            <h2>Chat Général</h2>
            <div id="chat-messages" ref="chatMessages">
                <div v-for="(msg, index) in messages" :key="index">
                    <strong>{{ msg.user }}:</strong> {{ msg.message }}
                </div>
            </div>
            <input type="text" id="chat-input" placeholder="Votre message..." v-model="newMessage" @keyup.enter="sendMessage">
            <button id="chat-send" @click="sendMessage">Envoyer</button>
        </aside>
    `
};
