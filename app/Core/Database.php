<?php
// app/Core/Database.php

namespace App\Core;

use PDO;
use PDOException;

class Database {
    private static $instance = null;
    private $connection;

    // Le constructeur est privé pour empêcher l'instanciation directe
    private function __construct() {
        $config = require __DIR__ . '/../../config/database.php'; // ou utilisez getenv() si vous utilisez .env

        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbname']};charset={$config['charset']}";
            $this->connection = new PDO($dsn, $config['user'], $config['password']);

            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // En production, vous mettriez un message plus générique
            die("Erreur de connexion à la base de données: " . $e->getMessage());
        }
    }

    // La méthode statique qui contrôle l'accès à l'instance
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Une méthode pour obtenir l'objet de connexion PDO
    public function getConnection() {
        return $this->connection;
    }
    
    // Empêcher le clonage de l'instance
    private function __clone() {}
}
