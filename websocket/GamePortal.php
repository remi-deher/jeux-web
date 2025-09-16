<?php
// /websocket/GamePortal.php

namespace WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use WebSocket\Games\MorpionHandler;
use WebSocket\Games\Puissance4Handler;
use WebSocket\Games\BatailleNavaleHandler;
use WebSocket\Games\PongHandler; // Ajout du Handler Pong
use React\EventLoop\Loop; // Ajout pour la boucle de jeu

class GamePortal implements MessageComponentInterface {
    protected $clients;
    protected $playerCounts;
    protected $gameHandlers;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->playerCounts = [];

        // On initialise tous les gestionnaires de jeux ici
        $this->gameHandlers = [
            'morpion' => new MorpionHandler(),
            'puissance4' => new Puissance4Handler(),
            'bataille_navale' => new BatailleNavaleHandler(),
            'pong' => new PongHandler() // Ajout du Handler Pong
        ];
        
        // Boucle de jeu pour Pong, s'exécute environ 60 fois par seconde
        Loop::addPeriodicTimer(1/60, function() {
            if (isset($this->gameHandlers['pong'])) {
                $this->gameHandlers['pong']->tick();
            }
        });
        
        echo "Serveur GamePortal initialisé avec les gestionnaires de jeux.\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion ! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        $type = $data['type'] ?? 'unknown';

        // Si un gestionnaire de jeu existe pour ce type, on lui passe le message
        if (isset($this->gameHandlers[$type])) {
            $this->gameHandlers[$type]->onMessage($from, $data, $this->clients);
            return;
        }

        // Sinon, on gère les messages globaux (chat, etc.)
        switch ($type) {
            case 'heartbeat':
                $gameId = (int)($data['gameId'] ?? 0);
                if ($gameId > 0) {
                    if (!isset($this->playerCounts[$gameId])) $this->playerCounts[$gameId] = 0;
                    $this->playerCounts[$gameId]++; 
                }
                break;

            case 'chat_message':
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
        // Notifier tous les gestionnaires de la déconnexion
        foreach ($this->gameHandlers as $handler) {
            if (method_exists($handler, 'onDisconnect')) {
                $handler->onDisconnect($conn, $this->clients);
            }
        }

        $this->clients->detach($conn);
        echo "Connexion {$conn->resourceId} fermée\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Une erreur est survenue: {$e->getMessage()}\n";
        $conn->close();
    }
}
