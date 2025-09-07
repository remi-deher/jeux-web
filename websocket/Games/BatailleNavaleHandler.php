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
            // ▼▼▼ AJOUT ▼▼▼
            case 'reset':
                $this->startNewRound();
                break;
            // ▲▲▲ FIN AJOUT ▲▲▲
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
    
    // ▼▼▼ NOUVELLE FONCTION ▼▼▼
    private function startNewRound() {
        if (count($this->state['players']) < 2) return;

        $this->state['phase'] = 'placement';
        $this->state['turn'] = null;
        $this->state['winner'] = null;
        $this->state['status'] = 'Nouvelle partie ! Placez vos navires.';

        // Réinitialise les données de chaque joueur pour la nouvelle manche
        foreach ($this->state['players'] as &$player) {
            $player['board'] = array_fill(0, self::GRID_SIZE, array_fill(0, self::GRID_SIZE, 'water'));
            $player['ships'] = [];
            $player['sunkShips'] = [];
            $player['shipsPlaced'] = false;
        }
        unset($player);
    }
    // ▲▲▲ FIN NOUVELLE FONCTION ▲▲▲

    private function fullReset() {
        $this->state = [
            'phase' => 'waiting',
            'players' => [],
            'turn' => null,
            'winner' => null,
            'status' => 'En attente d\'un adversaire...'
        ];
    }
    
    private function handleJoin(ConnectionInterface $conn) {
        foreach ($this->state['players'] as $player) {
            if ($player['id'] === $conn->resourceId) return;
        }
        
        if (count($this->state['players']) < 2) {
            $newPlayer = [
                'id' => $conn->resourceId,
                'board' => array_fill(0, self::GRID_SIZE, array_fill(0, self::GRID_SIZE, 'water')),
                'ships' => [], // Pour stocker la définition des navires
                'sunkShips' => [], // Pour stocker les noms des navires coulés
                'shipsPlaced' => false,
            ];
            $this->state['players'][] = $newPlayer;
        }
        
        if (count($this->state['players']) === 2) {
            $this->state['phase'] = 'placement';
            $this->state['status'] = 'Phase de placement. Placez vos navires.';
        }
    }

    private function handlePlaceShips(ConnectionInterface $from, $ships) {
        if ($this->state['phase'] !== 'placement' || count($ships) !== count(self::SHIPS)) return;

        foreach ($this->state['players'] as &$player) {
            if ($player['id'] === $from->resourceId) {
                $player['ships'] = $ships; // On sauvegarde la position des navires
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

        $allPlaced = true;
        if(count($this->state['players']) < 2) $allPlaced = false;
        foreach ($this->state['players'] as $player) {
            if (!$player['shipsPlaced']) $allPlaced = false;
        }

        if ($allPlaced) {
            $this->state['phase'] = 'battle';
            $this->state['turn'] = $this->state['players'][0]['id'];
            $this->state['status'] = 'Que la bataille commence ! Au tour du Joueur 1.';
        }
    }
    
    private function handleFireShot(ConnectionInterface $from, $coords) {
        if ($this->state['phase'] !== 'battle' || $from->resourceId !== $this->state['turn'] || !$coords) return;

        $opponentIdx = ($this->state['players'][0]['id'] === $from->resourceId) ? 1 : 0;
        
        $y = $coords['y'];
        $x = $coords['x'];
        $targetCell = &$this->state['players'][$opponentIdx]['board'][$y][$x];

        if ($targetCell === 'hit' || $targetCell === 'miss') return;

        if ($targetCell === 'ship') {
            $targetCell = 'hit';
            $this->state['status'] = 'Touché ! Vous pouvez rejouer.';

            // ▼▼▼ SECTION MODIFIÉE : Vérification si un navire est coulé ▼▼▼
            foreach ($this->state['players'][$opponentIdx]['ships'] as $ship) {
                if (!in_array($ship['name'], $this->state['players'][$opponentIdx]['sunkShips'])) {
                    $isSunk = true;
                    foreach ($ship['coords'] as $coord) {
                        if ($this->state['players'][$opponentIdx]['board'][$coord['y']][$coord['x']] !== 'hit') {
                            $isSunk = false;
                            break;
                        }
                    }
                    if ($isSunk) {
                        $this->state['players'][$opponentIdx]['sunkShips'][] = $ship['name'];
                        $this->state['status'] = 'Touché... Coulé ! (' . $ship['name'] . ') Rejouez !';
                    }
                }
            }
            // ▲▲▲ FIN SECTION MODIFIÉE ▲▲▲

        } else {
            $targetCell = 'miss';
            $this->state['turn'] = $this->state['players'][$opponentIdx]['id'];
            $nextPlayerNum = $opponentIdx + 1;
            $this->state['status'] = "Manqué ! Au tour du Joueur {$nextPlayerNum}.";
        }

        if (count($this->state['players'][$opponentIdx]['sunkShips']) === count(self::SHIPS)) {
            $this->state['phase'] = 'gameover';
            $this->state['winner'] = $from->resourceId;
            $winnerNum = ($opponentIdx === 1) ? 1 : 2;
            $this->state['status'] = "Tous les navires adverses sont coulés ! Le Joueur {$winnerNum} a gagné !";
        }
    }

    private function broadcastState($clients) {
        foreach ($clients as $client) {
            $payload = $this->buildPayloadFor($client->resourceId);
            $client->send(json_encode($payload));
        }
    }

    private function buildPayloadFor($playerId) {
        $playerIdx = -1;
        $opponentIdx = -1;
        foreach($this->state['players'] as $idx => $p) {
            if($p['id'] === $playerId) $playerIdx = $idx;
        }

        if ($playerIdx === -1) return ['type' => 'bataille_navale_state', 'state' => ['phase' => 'waiting']];
        
        $opponentIdx = ($playerIdx === 0 && count($this->state['players']) > 1) ? 1 : 0;

        $payload = [
            'type' => 'bataille_navale_state',
            'state' => [
                'phase' => $this->state['phase'],
                'isMyTurn' => $this->state['turn'] === $playerId,
                'status' => $this->state['status'],
                'winner' => $this->state['winner'],
                'myId' => $playerId, // Envoyer l'ID du joueur
                'myBoard' => $this->state['players'][$playerIdx]['board'],
                'opponentBoard' => [],
                'opponentSunkShips' => [],
            ]
        ];
        
        if(isset($this->state['players'][$opponentIdx])) {
            $payload['state']['opponentSunkShips'] = $this->state['players'][$opponentIdx]['sunkShips'];
            $opponentBoardView = [];
            foreach($this->state['players'][$opponentIdx]['board'] as $y => $row) {
                $opponentBoardView[$y] = [];
                foreach($row as $x => $cell) {
                    $opponentBoardView[$y][$x] = ($cell === 'hit' || $cell === 'miss') ? $cell : 'water';
                }
            }
            $payload['state']['opponentBoard'] = $opponentBoardView;
        }

        return $payload;
    }
}
