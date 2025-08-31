<?php
// /websocket/GamePortal.php

namespace WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use WebSocket\Games\MorpionHandler;

class GamePortal implements MessageComponentInterface {
    protected $clients;
    protected $playerCounts;
    protected $gameHandlers;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->playerCounts = [];

        $this->gameHandlers = [
            'morpion' => new MorpionHandler()
        ];
        echo "Serveur GamePortal initialisé avec les gestionnaires de jeux.\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion ! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $type = $data['type'] ?? 'unknown';

        if (isset($this->gameHandlers[$type])) {
            $this->gameHandlers[$type]->onMessage($from, $data, $this->clients);
            return;
        }

        switch ($type) {
            case 'heartbeat':
                // ▼▼▼ CODE MANQUANT RESTAURÉ ▼▼▼
                $gameId = (int)($data['gameId'] ?? 0);
                if ($gameId > 0) {
                    if (!isset($this->playerCounts[$gameId])) $this->playerCounts[$gameId] = 0;
                    $this->playerCounts[$gameId]++; 
                }
                // On pourrait appeler broadcastPlayerCounts() ici,
                // mais une gestion par cycle est souvent préférable.
                // Pour l'instant, laissons-le simple.
                break;

            case 'chat_message':
                // ▼▼▼ CODE MANQUANT RESTAURÉ ▼▼▼
                $messageData = [
                    'type' => 'new_chat_message',
                    'user' => 'Joueur' . $from->resourceId,
                    'message' => htmlspecialchars($data['message'] ?? '')
                ];
                // On renvoie le message à TOUS les clients connectés
                foreach ($this->clients as $client) {
                    $client->send(json_encode($messageData));
                }
                break;

            default:
                echo "Message de type global inconnu: {$type}\n";
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        foreach ($this->gameHandlers as $handler) {
            $handler->onDisconnect($conn, $this->clients);
        }

        $this->clients->detach($conn);
        echo "Connexion {$conn->resourceId} fermée\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Une erreur est survenue: {$e->getMessage()}\n";
        $conn->close();
    }
}
