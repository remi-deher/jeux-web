<?php
// /websocket/Games/MorpionHandler.php

namespace WebSocket\Games;

use Ratchet\ConnectionInterface;

class MorpionHandler {
    private $state;
    private $players; // [resourceId => 'x' | 'o']

    public function __construct() {
        $this->fullReset(); // On utilise la nouvelle fonction de reset complet au démarrage
    }
    
    public function onMessage(ConnectionInterface $from, $data, $clients) {
        $action = $data['action'] ?? 'unknown';

        switch ($action) {
            case 'join':
                $this->handleJoin($from);
                $this->broadcastState($clients); // Envoyer l'état après le join
                break;
            case 'move':
                $this->handleMove($from, $data['cellIndex'] ?? null);
                $this->broadcastState($clients); // Envoyer l'état après le coup
                break;
            case 'reset':
                // MODIFIÉ : On ne fait que commencer une nouvelle manche, on ne supprime pas les joueurs
                $this->startNewRound();
                $this->broadcastState($clients); // Envoyer l'état après le reset
                break;
        }
    }
    
    public function onDisconnect(ConnectionInterface $conn, $clients) {
        if (isset($this->players[$conn->resourceId])) {
            echo "Un joueur de morpion a quitté. Le jeu est complètement réinitialisé.\n";
            // On utilise le reset complet quand quelqu'un part
            $this->fullReset();
            $this->broadcastState($clients);
        }
    }
    
    /**
     * RENOMMÉE : Réinitialise complètement le jeu, y compris les joueurs.
     * À utiliser lors d'une déconnexion ou au démarrage du serveur.
     */
    private function fullReset() {
        echo "Réinitialisation complète du jeu Morpion.\n";
        $this->state = [
            'board' => array_fill(0, 9, null),
            'nextPlayer' => 'x',
            'isGameOver' => false,
            'status' => 'En attente de joueurs...'
        ];
        $this->players = [];
    }

    /**
     * NOUVELLE FONCTION : Réinitialise uniquement le plateau pour une nouvelle manche.
     * Les joueurs restent les mêmes.
     */
    private function startNewRound() {
        // Ne recommence que si la partie est bien finie
        if (!$this->state['isGameOver']) {
            return;
        }
        
        echo "Lancement d'une nouvelle manche de Morpion.\n";
        $this->state['board'] = array_fill(0, 9, null);
        $this->state['nextPlayer'] = 'x'; // X commence toujours la nouvelle manche (simple)
        $this->state['isGameOver'] = false;
        $this->state['status'] = 'Nouvelle manche ! Au tour du joueur X';
    }

    private function broadcastState($clients) {
        $payload = json_encode(['type' => 'morpion_state', 'state' => $this->state]);
        foreach ($clients as $client) {
            $client->send($payload);
        }
    }
    
    private function handleJoin(ConnectionInterface $conn) {
        if (count($this->players) < 2 && !isset($this->players[$conn->resourceId])) {
            $symbol = (count($this->players) === 0) ? 'x' : 'o';
            $this->players[$conn->resourceId] = $symbol;
            echo "Le joueur {$conn->resourceId} a rejoint la partie de morpion en tant que {$symbol}.\n";
        }
        
        if (count($this->players) < 2) {
            $this->state['status'] = "En attente d'un autre joueur...";
        } else {
            $this->state['status'] = "C'est au tour du joueur " . strtoupper($this->state['nextPlayer']);
        }
    }
    
    private function handleMove(ConnectionInterface $from, $index) {
        if ($index === null || $this->state['isGameOver'] || !isset($this->players[$from->resourceId]) || count($this->players) < 2) {
            return;
        }
        
        $playerSymbol = $this->players[$from->resourceId];
        
        if ($playerSymbol !== $this->state['nextPlayer']) {
            $from->send(json_encode(['type' => 'morpion_error', 'message' => 'Ce n\'est pas votre tour !']));
            return;
        }

        if ($this->state['board'][$index] !== null) {
            $from->send(json_encode(['type' => 'morpion_error', 'message' => 'Cette case est déjà prise !']));
            return;
        }

        $this->state['board'][$index] = $playerSymbol;
        
        $winner = $this->checkWin();
        if ($winner) {
            $this->state['isGameOver'] = true;
            $this->state['status'] = "Le joueur " . strtoupper($winner) . " a gagné !";
        } elseif (!in_array(null, $this->state['board'])) {
            $this->state['isGameOver'] = true;
            $this->state['status'] = "Match nul !";
        } else {
            $this->state['nextPlayer'] = ($playerSymbol === 'x') ? 'o' : 'x';
            $this->state['status'] = "C'est au tour du joueur " . strtoupper($this->state['nextPlayer']);
        }
    }

    private function checkWin() {
        $lines = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
        foreach ($lines as $line) {
            if ($this->state['board'][$line[0]] && $this->state['board'][$line[0]] === $this->state['board'][$line[1]] && $this->state['board'][$line[0]] === $this->state['board'][$line[2]]) {
                return $this->state['board'][$line[0]];
            }
        }
        return null;
    }
}
