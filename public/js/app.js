// public/js/app.js
document.addEventListener('DOMContentLoaded', () => {
    // L'adresse sera 'ws://localhost/ws/' grâce au reverse proxy
    const conn = new WebSocket('wss://jeux.grandepharmaciebonaparte.fr/ws/'); // Mettre l'adresse du reverse proxy en prod

    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const chatSend = document.getElementById('chat-send');

    conn.onopen = function(e) {
        console.log("Connexion WebSocket établie !");
    };

    conn.onmessage = function(e) {
        const data = JSON.parse(e.data);

        switch(data.type) {
            case 'player_count_update':
                updatePlayerCounts(data.counts);
                break;
            case 'new_chat_message':
                appendChatMessage(data);
                break;
        }
    };

    function updatePlayerCounts(counts) {
        // Remet à zéro les compteurs affichés
        document.querySelectorAll('.player-count .count').forEach(span => span.textContent = '0');

        for (const gameId in counts) {
            const countSpan = document.querySelector(`.player-count[data-game-id='${gameId}'] .count`);
            if (countSpan) {
                countSpan.textContent = counts[gameId];
            }
        }
    }

    function appendChatMessage(data) {
        const msgDiv = document.createElement('div');
        msgDiv.innerHTML = `<strong>${data.user}:</strong> ${data.message}`;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight; // Auto-scroll
    }

    chatSend.addEventListener('click', () => {
        const message = chatInput.value;
        if (message.trim() !== '') {
            conn.send(JSON.stringify({
                type: 'chat_message',
                message: message
            }));
            chatInput.value = '';
        }
    });
});
