// /public/js/app.js

// 1. Déclarer la connexion et un tableau de "callbacks" au niveau global
let conn;
window.onWsOpenCallbacks = []; // Fonctions à exécuter quand la connexion est prête

document.addEventListener('DOMContentLoaded', () => {
    // Détermine le protocole (ws ou wss) et l'URL
    const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
    const wsUrl = `${protocol}//${window.location.host}/ws/`;

    console.log(`Tentative de connexion au WebSocket sur : ${wsUrl}`);
    // 2. Assigner la connexion à la variable globale
    conn = new WebSocket(wsUrl);

    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const chatSend = document.getElementById('chat-send');

    conn.onopen = function(e) {
        console.log("Connexion WebSocket établie !");
        
        // 3. Quand la connexion est ouverte, exécuter toutes les fonctions en attente
        window.onWsOpenCallbacks.forEach(callback => callback());
    };

    conn.onmessage = function(e) {
        const data = JSON.parse(e.data);

        // Ce onmessage ne gère que le chat et le comptage, le jeu aura le sien
        switch(data.type) {
            case 'player_count_update':
                updatePlayerCounts(data.counts);
                break;
            case 'new_chat_message':
                // S'assurer que les éléments du chat existent avant de les manipuler
                if (chatMessages) {
                    appendChatMessage(data);
                }
                break;
        }
    };
    
    conn.onerror = function(e) {
        console.error("Erreur WebSocket observée:", e);
    };

    conn.onclose = function(e) {
        console.log("Connexion WebSocket fermée.");
    };

    function updatePlayerCounts(counts) {
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
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Gérer l'envoi de message du chat
    if (chatSend) {
        chatSend.addEventListener('click', () => {
            const message = chatInput.value;
            if (message.trim() !== '' && conn.readyState === WebSocket.OPEN) {
                conn.send(JSON.stringify({ type: 'chat_message', message: message }));
                chatInput.value = '';
            }
        });
    }
});
