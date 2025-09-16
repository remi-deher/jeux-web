<?php
// /websocket/Games/PongHandler.php

namespace WebSocket\Games;

use Ratchet\ConnectionInterface;

class PongHandler {
    private const CANVAS_WIDTH = 800;
    private const CANVAS_HEIGHT = 600;
    private const PADDLE_HEIGHT = 100;
    private const PADDLE_WIDTH = 10;
    private const BALL_SIZE = 10;

    private $state;
    private $players = []; // [id => connection]
    private $loop; // Pour la boucle de jeu

    public function __construct() {
        $this->fullReset();
    }
    
    // Cette fonction sera appelée par un Timer dans GamePortal
    public function tick() {
        if ($this->state['phase'] !== 'play') {
            return;
        }

        // 1. Mettre à jour la position de la balle
        $this->state['ball']['x'] += $this->state['ball']['vx'];
        $this->state['ball']['y'] += $this->state['ball']['vy'];

        // 2. Gérer les collisions avec les murs (haut/bas)
        if ($this->state['ball']['y'] - self::BALL_SIZE < 0 || $this->state['ball']['y'] + self::BALL_SIZE > self::CANVAS_HEIGHT) {
            $this->state['ball']['vy'] *= -1;
        }

        // 3. Gérer les collisions avec les raquettes
        // Raquette Joueur 1 (gauche)
        if ($this->state['ball']['x'] - self::BALL_SIZE < self::PADDLE_WIDTH &&
            $this->state['ball']['y'] > $this->state['paddles'][0]['y'] &&
            $this->state['ball']['y'] < $this->state['paddles'][0]['y'] + self::PADDLE_HEIGHT) {
            $this->state['ball']['vx'] *= -1.1; // Augmente la vitesse
        }
        // Raquette Joueur 2 (droite)
        if ($this->state['ball']['x'] + self::BALL_SIZE > self::CANVAS_WIDTH - self::PADDLE_WIDTH &&
            $this->state['ball']['y'] > $this->state['paddles'][1]['y'] &&
            $this->state['ball']['y'] < $this->state['paddles'][1]['y'] + self::PADDLE_HEIGHT) {
            $this->state['ball']['vx'] *= -1.1; // Augmente la vitesse
        }

        // 4. Gérer les points marqués
        if ($this->state['ball']['x'] < 0) {
            $this->state['scores'][1]++;
            $this->resetBall();
        } elseif ($this->state['ball']['x'] > self::CANVAS_WIDTH) {
            $this->state['scores'][0]++;
            $this->resetBall();
        }

        // 5. Envoyer le nouvel état à tous les joueurs
        $this->broadcastStateToPlayers();
    }


    public function onMessage(ConnectionInterface $from, $data, $clients) {
        $action = $data['action'] ?? 'unknown';

        switch ($action) {
            case 'join':
                $this->handleJoin($from);
                $this->broadcastStateToPlayers();
                break;
            case 'move':
                $this->handleMove($from, $data['y'] ?? null);
                break;
        }
    }
    
    public function onDisconnect(ConnectionInterface $conn, $clients) {
        if (isset($this->players[$conn->resourceId])) {
            echo "Un joueur de Pong a quitté. Le jeu est réinitialisé.\n";
            unset($this->players[$conn->resourceId]);
            $this->fullReset();
            $this->broadcastStateToPlayers();
        }
    }
    
    private function fullReset() {
        $this->state = [
            'phase' => 'waiting',
            'ball' => [
                'x' => self::CANVAS_WIDTH / 2,
                'y' => self::CANVAS_HEIGHT / 2,
                'vx' => 5,
                'vy' => 5,
            ],
            'paddles' => [
                ['y' => self::CANVAS_HEIGHT / 2 - self::PADDLE_HEIGHT / 2],
                ['y' => self::CANVAS_HEIGHT / 2 - self::PADDLE_HEIGHT / 2]
            ],
            'scores' => [0, 0],
            'status' => 'En attente d\'un adversaire...'
        ];
        $this->players = [];
    }

    private function resetBall() {
        $this->state['ball']['x'] = self::CANVAS_WIDTH / 2;
        $this->state['ball']['y'] = self::CANVAS_HEIGHT / 2;
        $this->state['ball']['vx'] = (rand(0, 1) === 0 ? 5 : -5); // Direction aléatoire
        $this->state['ball']['vy'] = (rand(0, 1) === 0 ? 5 : -5);
    }
    
    private function broadcastStateToPlayers() {
        $playerIds = array_keys($this->players);
        foreach ($playerIds as $index => $id) {
            $conn = $this->players[$id];
            $payload = [
                'type' => 'pong_state',
                'state' => $this->state,
                'playerIndex' => $index // 0 pour le joueur 1, 1 pour le joueur 2
            ];
            $conn->send(json_encode($payload));
        }
    }
    
    private function handleJoin(ConnectionInterface $conn) {
        if (count($this->players) < 2 && !isset($this->players[$conn->resourceId])) {
            $this->players[$conn->resourceId] = $conn;
            echo "Le joueur {$conn->resourceId} a rejoint la partie de Pong.\n";
        }
        
        if (count($this->players) === 2) {
            $this->state['phase'] = 'play';
            $this->state['status'] = 'La partie commence !';
        }
    }
    
    private function handleMove(ConnectionInterface $from, $y) {
        $playerIds = array_keys($this->players);
        $playerIndex = array_search($from->resourceId, $playerIds);

        if ($playerIndex !== false && $y !== null) {
            // S'assurer que la raquette ne sort pas du canvas
            $newY = max(0, min($y, self::CANVAS_HEIGHT - self::PADDLE_HEIGHT));
            $this->state['paddles'][$playerIndex]['y'] = $newY;
        }
    }
}
