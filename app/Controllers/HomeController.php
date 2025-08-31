<?php
// /app/Controllers/HomeController.php

namespace App\Controllers;

use App\Models\GameModel; // N'oubliez pas d'importer le modèle

class HomeController {
    /**
     * Affiche la page d'accueil.
     */
    public function index() {
        // 1. Instancier le modèle pour interagir avec la base de données
        $gameModel = new GameModel();
        
        // 2. Récupérer les données nécessaires (la liste de tous les jeux)
        $games = $gameModel->getAllGames();
        
        // 3. (Bonne pratique) Préparer d'autres variables pour la vue, comme le titre
        $title = 'Accueil - Mon Portail de Jeux';

        // 4. Charger le fichier de la vue principale.
        // Les variables $games et $title définies ici seront automatiquement
        // accessibles dans le fichier 'home.php' et ses layouts (header/footer).
        require __DIR__ . '/../Views/home.php';
    }
}
