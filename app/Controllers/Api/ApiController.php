<?php
// /app/Controllers/api/ApiController.php

namespace App\Controllers\Api;

use App\Models\GameModel;

class ApiController {

    /**
     * Retourne la liste des jeux au format JSON.
     */
    public function getGames() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *'); // Utile pour le développement si jamais vous séparez les domaines
        
        try {
            $gameModel = new GameModel();
            $games = $gameModel->getAllGames();
            echo json_encode($games);
        } catch (\Exception $e) {
            // Si une erreur se produit (ex: connexion DB), on renvoie un JSON d'erreur propre
            http_response_code(500);
            echo json_encode(['error' => 'An internal server error occurred.', 'message' => $e->getMessage()]);
        }
    }
}
