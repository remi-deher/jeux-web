<?php
// websocket/server.php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

class GamePortal implements MessageComponentInterface {
    protected $clients;
    protected $playerCounts; // [gameId => count]

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->playerCounts = [];
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "Nouvelle connexion ! ({$conn->resourceId})\n";

        // Envoyer les comptes de joueurs actuels au nouveau client
        $conn->send(json_encode(['type' => 'player_count_update', 'counts' => $this->playerCounts]));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        switch ($data['type']) {
            // Le "Heartbeat" vient d'une page de jeu
            case 'heartbeat':
                $gameId = (int)$data['gameId'];
                // Logique simplifiée : on suppose qu'un heartbeat = un joueur
                // En réalité, il faudrait gérer les déconnexions pour décrémenter
                if (!isset($this->playerCounts[$gameId])) {
                    $this->playerCounts[$gameId] = 0;
                }
                $this->playerCounts[$gameId]++; // À améliorer avec une vraie gestion de session
                $this->broadcastPlayerCounts();
                break;

            // Un message du chat
            case 'chat_message':
                $messageData = [
                    'type' => 'new_chat_message',
                    'user' => 'Utilisateur' . $from->resourceId, // Simplifié
                    'message' => htmlspecialchars($data['message'])
                ];
                foreach ($this->clients as $client) {
                    $client->send(json_encode($messageData));
                }
                break;
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connexion {$conn->resourceId} fermée\n";
        // Ici, il faudrait une logique pour décrémenter le compteur du jeu auquel l'utilisateur jouait
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Une erreur est survenue: {$e->getMessage()}\n";
        $conn->close();
    }

    // Fonction pour envoyer les comptes à tout le monde
    public function broadcastPlayerCounts() {
        $payload = json_encode(['type' => 'player_count_update', 'counts' => $this->playerCounts]);
        foreach ($this->clients as $client) {
            $client->send($payload);
        }
    }
}

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new GamePortal()
        )
    ),
    8095 // Port d'écoute du serveur WebSocket
);

echo "Serveur WebSocket démarré sur le port 8095\n";
$server->run();
