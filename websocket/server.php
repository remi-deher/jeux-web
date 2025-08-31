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
use WebSocket\GamePortal; // On importe notre classe principale

// --- LANCEMENT DU SERVEUR ---
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new GamePortal()
        )
    ),
    8095
);

echo "Serveur WebSocket démarré sur le port 8095\n";
$server->run();
