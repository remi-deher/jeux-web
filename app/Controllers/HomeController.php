<?php
// app/Controllers/HomeController.php

namespace App\Controllers;

use App\Models\GameModel;

class HomeController {
    public function index() {
        // 1. Demander les données au Modèle
        $gameModel = new GameModel();
        $games = $gameModel->getAllGames();

        // 2. Charger la Vue et lui passer les données
        // La variable $games sera accessible dans home.php
        require __DIR__ . '/../Views/home.php';
    }
}
