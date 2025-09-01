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
    
    // ▼▼▼ SECTION MODIFIÉE ▼▼▼
    private function broadcastState($clients) {
        foreach ($clients as $client) {
            // On détermine la couleur du joueur actuel
            $playerColor = $this->players[$client->resourceId] ?? null;

            // On ajoute la couleur du joueur à l'état envoyé
            $stateForPlayer = $this->state;
            $stateForPlayer['playerColor'] = $playerColor;

            $payload = json_encode(['type' => 'puissance4_state', 'state' => $stateForPlayer]);
            $client->send($payload);
        }
    }
    // ▲▲▲ FIN SECTION MODIFIÉE ▲▲▲

    private function fullReset() {
        $this->state = [
            'board' => array_fill(0, self::ROWS, array_fill(0, self::COLS, null)),
            'nextPlayer' => 'red',
            'isGameOver' => false,
            'status' => 'En attente de joueurs...'
        ];
        $this->players = [];
    }
    
    private function startNewRound() {
        if (!$this->state['isGameOver']) return;
        $this->state['board'] = array_fill(0, self::ROWS, array_fill(0, self::COLS, null));
        $this->state['nextPlayer'] = 'red';
        $this->state['isGameOver'] = false;
        $this->state['status'] = 'Nouvelle manche ! Au tour du joueur Rouge';
    }

    private function handleJoin(ConnectionInterface $conn) {
        if (count($this->players) < 2 && !isset($this->players[$conn->resourceId])) {
            $color = (count($this->players) === 0) ? 'red' : 'yellow';
            $this->players[$conn->resourceId] = $color;
            echo "Le joueur {$conn->resourceId} a rejoint la partie de Puissance 4 en tant que {$color}.\n";
        }

        if (count($this->players) === 2) {
            $this->state['status'] = "C'est au tour du joueur " . ucfirst($this->state['nextPlayer']);
        }
    }

    private function handleMove(ConnectionInterface $from, $col) {
        if ($col === null || $this->state['isGameOver'] || !isset($this->players[$from->resourceId]) || count($this->players) < 2) {
            return;
        }

        $playerColor = $this->players[$from->resourceId];
        if ($playerColor !== $this->state['nextPlayer']) {
            return; // Pas son tour
        }
        
        if ($col < 0 || $col >= self::COLS || $this->state['board'][0][$col] !== null) {
            return; // Mouvement invalide
        }

        $row = -1;
        for ($i = self::ROWS - 1; $i >= 0; $i--) {
            if ($this->state['board'][$i][$col] === null) {
                $this->state['board'][$i][$col] = $playerColor;
                $row = $i;
                break;
            }
        }

        if ($this->checkWin($row, $col, $playerColor)) {
            $this->state['isGameOver'] = true;
            $this->state['status'] = "Le joueur " . ucfirst($playerColor) . " a gagné !";
        } elseif ($this->isBoardFull()) {
             $this->state['isGameOver'] = true;
             $this->state['status'] = "Match nul !";
        } else {
            $this->state['nextPlayer'] = ($playerColor === 'red') ? 'yellow' : 'red';
            $this->state['status'] = "C'est au tour du joueur " . ucfirst($this->state['nextPlayer']);
        }
    }

    private function checkWin($row, $col, $color) {
        $directions = [[0, 1], [1, 0], [1, 1], [1, -1]];
        foreach ($directions as $dir) {
            $count = 1;
            for ($i = 1; $i < 4; $i++) {
                $r = $row + $dir[0] * $i;
                $c = $col + $dir[1] * $i;
                if ($r >= 0 && $r < self::ROWS && $c >= 0 && $c < self::COLS && $this->state['board'][$r][$c] === $color) {
                    $count++;
                } else {
                    break;
                }
            }
            for ($i = 1; $i < 4; $i++) {
                $r = $row - $dir[0] * $i;
                $c = $col - $dir[1] * $i;
                 if ($r >= 0 && $r < self::ROWS && $c >= 0 && $c < self::COLS && $this->state['board'][$r][$c] === $color) {
                    $count++;
                } else {
                    break;
                }
            }
            if ($count >= 4) return true;
        }
        return false;
    }

    private function isBoardFull() {
        for ($c = 0; $c < self::COLS; $c++) {
            if ($this->state['board'][0][$c] === null) {
                return false;
            }
        }
        return true;
    }
}
