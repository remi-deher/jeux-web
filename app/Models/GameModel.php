<?php
// /app/Models/GameModel.php

namespace App\Models;

use App\Core\Database;
use PDO;

class GameModel {
    private $db;

    public function __construct() {
        // Utilise la classe Database dédiée pour obtenir la connexion
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les jeux de la base de données.
     * @return array
     */
    public function getAllGames() {
        $stmt = $this->db->query("SELECT id, name, description, slug, image_url FROM games ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    /**
     * Trouve un jeu spécifique par son ID.
     * @param int $id
     * @return mixed
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM games WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Crée un nouveau jeu en base de données.
     * @param array $data Données du jeu (name, description, slug, image_url)
     * @return string L'ID du jeu nouvellement créé.
     */
    public function create($data) {
        $sql = "INSERT INTO games (name, description, slug, image_url) VALUES (:name, :description, :slug, :image_url)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':slug' => $data['slug'],
            ':image_url' => $data['image_url'] ?? '/images/default.jpg' // Fournir une image par défaut
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Met à jour un jeu existant.
     * @param int $id ID du jeu à mettre à jour.
     * @param array $data Données du jeu.
     * @return bool
     */
    public function update($id, $data) {
        // Construit la requête dynamiquement si une nouvelle image est fournie
        if (!empty($data['image_url'])) {
            $sql = "UPDATE games SET name = :name, description = :description, slug = :slug, image_url = :image_url WHERE id = :id";
            $params = [
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':slug' => $data['slug'],
                ':image_url' => $data['image_url'],
                ':id' => $id
            ];
        } else {
            // Requête sans mise à jour de l'image
            $sql = "UPDATE games SET name = :name, description = :description, slug = :slug WHERE id = :id";
            $params = [
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':slug' => $data['slug'],
                ':id' => $id
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Supprime un jeu de la base de données.
     * @param int $id ID du jeu à supprimer.
     * @return bool
     */
    public function delete($id) {
        // Note : Pour une version plus complète, on supprimerait aussi le fichier image du disque ici.
        // $game = $this->find($id);
        // if ($game && file_exists(__DIR__ . '/../../public' . $game['image_url'])) {
        //     unlink(__DIR__ . '/../../public' . $game['image_url']);
        // }
        
        $stmt = $this->db->prepare("DELETE FROM games WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
