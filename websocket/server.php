<?php
// /websocket/server.php

require dirname(__DIR__) . '/vendor/autoload.php';

// Ajouter une fonction d'autoloading pour nos classes dans le dossier WebSocket
spl_autoload_register(function ($class) {
    $prefix = 'WebSocket\\';
    $base_dir = __DIR__ . '/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use WebSocket\GamePortal;
use React\EventLoop\Loop; // Assurez-vous que cette ligne est présente

// --- LANCEMENT DU SERVEUR (VERSION CORRIGÉE) ---

// 1. On récupère la boucle d'événements globale
$loop = Loop::get();

// 2. On passe la boucle à notre application de jeu
$gamePortal = new GamePortal($loop);

// 3. On construit le serveur
$server = new IoServer(
    new HttpServer(
        new WsServer(
            $gamePortal
        )
    ),
    new \React\Socket\SocketServer('0.0.0.0:8095', [], $loop), // On passe aussi la boucle au serveur socket
    $loop
);

echo "Serveur WebSocket démarré sur le port 8095\n";
$server->run();
