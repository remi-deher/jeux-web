<?php
// /websocket/Games/MorpionHandler.php

namespace WebSocket\Games;

use Ratchet\ConnectionInterface;

class MorpionHandler {
    private $state;
    private $players; // [resourceId => 'x' | 'o']

    public function __construct() {
        $this->fullReset();
    }
    
    public function onMessage(ConnectionInterface $from, $data, $clients) {
        $action = $data['action'] ?? 'unknown';

        switch ($action) {
            case 'join':
                $this->handleJoin($from);
                break;
            case 'move':
                $this->handleMove($from, $data['cellIndex'] ?? null);
                break;
            case 'reset':
                $this->startNewRound();
                break;
        }
        // On envoie l'état après chaque action
        $this->broadcastState($clients);
    }
    
    public function onDisconnect(ConnectionInterface $conn, $clients) {
        if (isset($this->players[$conn->resourceId])) {
            echo "Un joueur de morpion a quitté. Le jeu est complètement réinitialisé.\n";
            unset($this->players[$conn->resourceId]); // On retire juste le joueur
            $this->fullReset(); // On réinitialise complètement
            $this->broadcastState($clients);
        }
    }
    
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

    private function startNewRound() {
        if (!$this->state['isGameOver']) {
            return;
        }
        
        echo "Lancement d'une nouvelle manche de Morpion.\n";
        $this->state['board'] = array_fill(0, 9, null);
        $this->state['nextPlayer'] = 'x';
        $this->state['isGameOver'] = false;
        $this->state['status'] = 'Nouvelle manche ! Au tour du joueur X';
    }

    // ▼▼▼ SECTION MODIFIÉE ▼▼▼
    private function broadcastState($clients) {
        foreach ($clients as $client) {
            // On détermine le symbole du joueur actuel
            $playerSymbol = $this->players[$client->resourceId] ?? null;

            // On ajoute le symbole du joueur à l'état envoyé
            $stateForPlayer = $this->state;
            $stateForPlayer['playerSymbol'] = $playerSymbol;

            $payload = json_encode(['type' => 'morpion_state', 'state' => $stateForPlayer]);
            $client->send($payload);
        }
    }
    // ▲▲▲ FIN SECTION MODIFIÉE ▲▲▲
    
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
