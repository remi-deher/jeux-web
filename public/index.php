<?php
// public/index.php
session_start();

// Autoloader de Composer
require __DIR__ . '/../vendor/autoload.php';

// --- ROUTEUR SIMPLE ---
$requestUri = strtok($_SERVER["REQUEST_URI"], '?'); // URL sans les paramètres GET

// Définition des routes
$routes = [
    '/' => ['App\Controllers\HomeController', 'index'],
    '/admin/login' => ['App\Controllers\AdminController', 'login'],
    '/admin/logout' => ['App\Controllers\AdminController', 'logout'],
    '/admin' => ['App\Controllers\AdminController', 'dashboard'],
    '/admin/create' => ['App\Controllers\AdminController', 'create'],
    '/admin/store' => ['App\Controllers\AdminController', 'store'],
    '/admin/edit' => ['App\Controllers\AdminController', 'edit'],
    '/admin/update' => ['App\Controllers\AdminController', 'update'],
    '/admin/delete' => ['App\Controllers\AdminController', 'delete'],
];

if (array_key_exists($requestUri, $routes)) {
    $controllerName = $routes[$requestUri][0];
    $methodName = $routes[$requestUri][1];

    $controller = new $controllerName();
    $controller->$methodName();
} else {
    // Gérer les erreurs 404
    http_response_code(404);
    echo "<h1>Page non trouvée (404)</h1>";
}
