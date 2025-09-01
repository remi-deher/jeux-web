<?php
// public/index.php
session_start();

// Autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// --- ROUTEUR SIMPLE ---
$requestUri = strtok($_SERVER["REQUEST_URI"], '?');

// --- Routes API ---
if (strpos($requestUri, '/api/') === 0) {
    // Affiche les erreurs PHP pour le débogage (à retirer en production)
    ini_set('display_errors', 1);
    error_reporting(E_ALL);

    $apiRoutes = [
        '/api/games' => ['App\Controllers\Api\ApiController', 'getGames'],
        // Ajoutez d'autres routes API ici
    ];

    if (array_key_exists($requestUri, $apiRoutes)) {
        $controllerName = $apiRoutes[$requestUri][0];
        $methodName = $apiRoutes[$requestUri][1];

        $controller = new $controllerName();
        $controller->$methodName();
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
    }
    exit; // Arrêter le script après avoir traité une route API
}

// --- Routes d'administration (inchangées) ---
if (strpos($requestUri, '/admin') === 0) {
    $adminRoutes = [
        '/admin/login' => ['App\Controllers\AdminController', 'login'],
        '/admin/logout' => ['App\Controllers\AdminController', 'logout'],
        '/admin' => ['App\Controllers\AdminController', 'dashboard'],
        '/admin/create' => ['App\Controllers\AdminController', 'create'],
        '/admin/store' => ['App\Controllers\AdminController', 'store'],
        '/admin/edit' => ['App\Controllers\AdminController', 'edit'],
        '/admin/update' => ['App\Controllers\AdminController', 'update'],
        '/admin/delete' => ['App\Controllers\AdminController', 'delete'],
    ];

    if (array_key_exists($requestUri, $adminRoutes)) {
        $controllerName = $adminRoutes[$requestUri][0];
        $methodName = $adminRoutes[$requestUri][1];

        $controller = new $controllerName();
        $controller->$methodName();
    } else {
        // Pour les autres routes /admin non définies, vous pouvez rediriger ou afficher une 404 admin
        http_response_code(404);
        echo "<h1>Page d'administration non trouvée (404)</h1>";
    }
    exit; // Arrêter le script
}


// --- Pour toutes les autres routes, on sert l'application Vue.js ---
// Le fichier index.html sera le point d'entrée de la SPA
require __DIR__ . '/index.html';
