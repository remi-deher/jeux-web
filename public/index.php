<?php
// public/index.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Inclure l'autoloader magique de Composer
require __DIR__ . '/../vendor/autoload.php';

// Pour l'instant, on appelle directement le contrôleur de l'accueil
$controller = new App\Controllers\HomeController();
$controller->index();
