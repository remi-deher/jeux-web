<?php
// /websocket/Games/Puissance4Handler.php

namespace WebSocket\Games;

use Ratchet\ConnectionInterface;

class Puissance4Handler {
    private const ROWS = 6;
    private const COLS = 7;

    private $state;
    private $players; // [resourceId => 'red' | 'yellow']

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
                $this->handleMove($from, $data['columnIndex'] ?? null);
                break;
            case 'reset':
                $this->startNewRound();
                break;
        }
        
        $this->broadcastState($clients);
    }

    public function onDisconnect(ConnectionInterface $conn, $clients) {
        if (isset($this->players[$conn->resourceId])) {
            echo "Un joueur de Puissance 4 a quitté. Le jeu est réinitialisé.\n";
            $this->fullReset();
            $this->broadcastState($clients);
        }
    }

    private function fullReset() {
        $this->state = [
            'board' => array_fill(0, self::ROWS * self::COLS, null),
            'nextPlayer' => 'red',
            'isGameOver' => false,
            'status' => 'En attente de joueurs...'
        ];
        $this->players = [];
    }

    private function startNewRound() {
        if (!$this->state['isGameOver']) return;
        
        $this->state['board'] = array_fill(0, self::ROWS * self::COLS, null);
        $this->state['nextPlayer'] = 'red';
        $this->state['isGameOver'] = false;
        $this->state['status'] = 'Nouvelle manche ! Au tour du joueur rouge';
    }

    private function broadcastState($clients) {
        $payload = json_encode(['type' => 'puissance4_state', 'state' => $this->state]);
        foreach ($clients as $client) $client->send($payload);
    }
    
    private function handleJoin(ConnectionInterface $conn) {
        if (count($this->players) < 2 && !isset($this->players[$conn->resourceId])) {
            $color = (count($this->players) === 0) ? 'red' : 'yellow';
            $this->players[$conn->resourceId] = $color;
            echo "Joueur {$conn->resourceId} a rejoint Puissance 4 en tant que {$color}.\n";
        }
        
        if (count($this->players) < 2) {
            $this->state['status'] = "En attente d'un autre joueur...";
        } else {
            $this->state['status'] = "C'est au tour du joueur {$this->state['nextPlayer']}";
        }
    }
    
    private function handleMove(ConnectionInterface $from, $col) {
        if ($col === null || $this->state['isGameOver'] || !isset($this->players[$from->resourceId]) || count($this->players) < 2) return;
        
        $playerColor = $this->players[$from->resourceId];
        if ($playerColor !== $this->state['nextPlayer']) return;

        // Logique de "chute" du jeton
        for ($row = self::ROWS - 1; $row >= 0; $row--) {
            $index = $row * self::COLS + $col;
            if ($this->state['board'][$index] === null) {
                $this->state['board'][$index] = $playerColor;
                
                // Vérifier la victoire
                if ($this->checkWin($playerColor)) {
                    $this->state['isGameOver'] = true;
                    $this->state['status'] = "Le joueur {$playerColor} a gagné !";
                } elseif (count(array_filter($this->state['board'])) === self::ROWS * self::COLS) {
                    $this->state['isGameOver'] = true;
                    $this->state['status'] = "Match nul !";
                } else {
                    $this->state['nextPlayer'] = ($playerColor === 'red') ? 'yellow' : 'red';
                    $this->state['status'] = "C'est au tour du joueur {$this->state['nextPlayer']}";
                }
                return; // Le coup a été joué
            }
        }
    }

    private function checkWin($player) {
        // Horizontale
        for ($r = 0; $r < self::ROWS; $r++) {
            for ($c = 0; $c <= self::COLS - 4; $c++) {
                if ($this->state['board'][$r*self::COLS+$c] == $player && $this->state['board'][$r*self::COLS+$c+1] == $player && $this->state['board'][$r*self::COLS+$c+2] == $player && $this->state['board'][$r*self::COLS+$c+3] == $player) return true;
            }
        }
        // Verticale
        for ($r = 0; $r <= self::ROWS - 4; $r++) {
            for ($c = 0; $c < self::COLS; $c++) {
                if ($this->state['board'][$r*self::COLS+$c] == $player && $this->state['board'][($r+1)*self::COLS+$c] == $player && $this->state['board'][($r+2)*self::COLS+$c] == $player && $this->state['board'][($r+3)*self::COLS+$c] == $player) return true;
            }
        }
        // Diagonale (descendante)
        for ($r = 0; $r <= self::ROWS - 4; $r++) {
            for ($c = 0; $c <= self::COLS - 4; $c++) {
                if ($this->state['board'][$r*self::COLS+$c] == $player && $this->state['board'][($r+1)*self::COLS+$c+1] == $player && $this->state['board'][($r+2)*self::COLS+$c+2] == $player && $this->state['board'][($r+3)*self::COLS+$c+3] == $player) return true;
            }
        }
        // Diagonale (ascendante)
        for ($r = 3; $r < self::ROWS; $r++) {
            for ($c = 0; $c <= self::COLS - 4; $c++) {
                if ($this->state['board'][$r*self::COLS+$c] == $player && $this->state['board'][($r-1)*self::COLS+$c+1] == $player && $this->state['board'][($r-2)*self::COLS+$c+2] == $player && $this->state['board'][($r-3)*self::COLS+$c+3] == $player) return true;
            }
        }
        return false;
    }
}
