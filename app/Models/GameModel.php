<?php
// app/Models/GameModel.php

namespace App\Models;

use App\Core\Database; // <-- On importe notre nouvelle classe
use PDO;

class GameModel {
    private $db;

    public function __construct() {
        // On demande simplement la connexion à notre classe dédiée
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllGames() {
        $stmt = $this->db->query("SELECT id, name, description, slug, image_url FROM games ORDER BY name ASC");
        return $stmt->fetchAll();
    }
}
