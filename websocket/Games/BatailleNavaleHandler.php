<?php
// /websocket/Games/BatailleNavaleHandler.php

namespace WebSocket\Games;

use Ratchet\ConnectionInterface;

class BatailleNavaleHandler {
    private const GRID_SIZE = 10;
    private const SHIPS = [
        'porte-avions' => 5,
        'croiseur' => 4,
        'contre-torpilleur' => 3,
        'sous-marin' => 3,
        'torpilleur' => 2
    ];

    private $state;

    public function __construct() {
        $this->fullReset();
    }

    public function onMessage(ConnectionInterface $from, $data, $clients) {
        $action = $data['action'] ?? 'unknown';

        switch ($action) {
            case 'join':
                $this->handleJoin($from);
                break;
            case 'place_ships':
                $this->handlePlaceShips($from, $data['ships'] ?? []);
                break;
            case 'fire_shot':
                $this->handleFireShot($from, $data['coords'] ?? null);
                break;
        }
        $this->broadcastState($clients);
    }

    public function onDisconnect(ConnectionInterface $conn, $clients) {
        $playerFound = false;
        foreach ($this->state['players'] as $player) {
            if ($player['id'] === $conn->resourceId) {
                $playerFound = true;
                break;
            }
        }
        if ($playerFound) {
            echo "Un joueur de Bataille Navale a quitté. Le jeu est réinitialisé.\n";
            $this->fullReset();
            $this->broadcastState($clients);
        }
    }

    private function fullReset() {
        $this->state = [
            'phase' => 'waiting', // waiting, placement, battle, gameover
            'players' => [],
            'turn' => null,
            'winner' => null,
            'status' => 'En attente d\'un adversaire...'
        ];
    }
    
    private function handleJoin(ConnectionInterface $conn) {
        if (count($this->state['players']) < 2) {
            $newPlayer = [
                'id' => $conn->resourceId,
                'board' => array_fill(0, self::GRID_SIZE, array_fill(0, self::GRID_SIZE, 'water')),
                'shipsPlaced' => false,
            ];
            $this->state['players'][] = $newPlayer;
            echo "Joueur {$conn->resourceId} a rejoint la Bataille Navale.\n";
        }
        
        if (count($this->state['players']) === 2) {
            $this->state['phase'] = 'placement';
            $this->state['status'] = 'Phase de placement. Placez vos navires.';
        }
    }

    private function handlePlaceShips(ConnectionInterface $from, $ships) {
        // Validation basique des navires (pourrait être plus robuste)
        if ($this->state['phase'] !== 'placement' || count($ships) !== count(self::SHIPS)) return;

        foreach ($this->state['players'] as &$player) {
            if ($player['id'] === $from->resourceId) {
                // Placer les navires sur le plateau du joueur
                foreach($ships as $ship) {
                    foreach($ship['coords'] as $coord) {
                        $player['board'][$coord['y']][$coord['x']] = 'ship';
                    }
                }
                $player['shipsPlaced'] = true;
                break;
            }
        }
        unset($player);

        // Vérifier si les deux joueurs ont placé leurs navires
        $allPlaced = true;
        foreach ($this->state['players'] as $player) {
            if (!$player['shipsPlaced']) {
                $allPlaced = false;
                break;
            }
        }

        if ($allPlaced) {
            $this->state['phase'] = 'battle';
            $this->state['turn'] = $this->state['players'][0]['id']; // Le premier joueur commence
            $this->state['status'] = 'Que la bataille commence ! Au tour du Joueur 1.';
        }
    }
    
    private function handleFireShot(ConnectionInterface $from, $coords) {
        if ($this->state['phase'] !== 'battle' || $from->resourceId !== $this->state['turn'] || !$coords) return;

        $opponentIdx = ($this->state['players'][0]['id'] === $from->resourceId) ? 1 : 0;
        
        $y = $coords['y'];
        $x = $coords['x'];
        $targetCell = &$this->state['players'][$opponentIdx]['board'][$y][$x];

        if ($targetCell === 'hit' || $targetCell === 'miss') return; // Déjà tiré ici

        if ($targetCell === 'ship') {
            $targetCell = 'hit';
            $this->state['status'] = 'Touché ! Vous pouvez rejouer.';
            // En bataille navale, un "touché" permet de rejouer. On ne change pas le tour.
        } else {
            $targetCell = 'miss';
            // Changer de tour
            $this->state['turn'] = $this->state['players'][$opponentIdx]['id'];
            $nextPlayerNum = $opponentIdx + 1;
            $this->state['status'] = "Manqué ! Au tour du Joueur {$nextPlayerNum}.";
        }

        // Vérifier si la partie est terminée
        $opponentShipsLeft = false;
        foreach ($this->state['players'][$opponentIdx]['board'] as $row) {
            if (in_array('ship', $row)) {
                $opponentShipsLeft = true;
                break;
            }
        }

        if (!$opponentShipsLeft) {
            $this->state['phase'] = 'gameover';
            $this->state['winner'] = $from->resourceId;
            $winnerNum = ($opponentIdx === 1) ? 1 : 2;
            $this->state['status'] = "Coulé ! Le Joueur {$winnerNum} a gagné la partie !";
        }
    }

    private function broadcastState($clients) {
        foreach ($clients as $client) {
            // Créer une vue personnalisée de l'état pour chaque joueur
            $payload = $this->buildPayloadFor($client->resourceId);
            $client->send(json_encode($payload));
        }
    }

    private function buildPayloadFor($playerId) {
        $payload = [
            'type' => 'bataille_navale_state',
            'state' => [
                'phase' => $this->state['phase'],
                'isMyTurn' => $this->state['turn'] === $playerId,
                'status' => $this->state['status'],
                'winner' => $this->state['winner'],
                'myBoard' => [],
                'opponentBoard' => []
            ]
        ];

        if (count($this->state['players']) < 2) return $payload;

        $playerIdx = ($this->state['players'][0]['id'] === $playerId) ? 0 : 1;
        $opponentIdx = ($playerIdx === 0) ? 1 : 0;

        // Le plateau du joueur avec ses navires visibles
        $payload['state']['myBoard'] = $this->state['players'][$playerIdx]['board'];

        // Le plateau de l'adversaire avec les navires cachés
        $opponentBoardView = [];
        if(isset($this->state['players'][$opponentIdx])) {
            foreach($this->state['players'][$opponentIdx]['board'] as $y => $row) {
                $opponentBoardView[$y] = [];
                foreach($row as $x => $cell) {
                    // On ne montre que les tirs (hit/miss), pas les navires (ship)
                    $opponentBoardView[$y][$x] = ($cell === 'hit' || $cell === 'miss') ? $cell : 'water';
                }
            }
        }
        $payload['state']['opponentBoard'] = $opponentBoardView;

        return $payload;
    }
}
