<?php
// /app/Controllers/AdminController.php

namespace App\Controllers;

use App\Models\GameModel;

class AdminController {

    private $gameModel;
    private $adminConfig;

    public function __construct() {
        $this->gameModel = new GameModel();
        // Charge les identifiants de l'admin depuis un fichier de configuration
        $this->adminConfig = require __DIR__ . '/../../config/admin.php';

        // Gère la sécurité de la section admin
        $this->protectRoutes();
    }

    /**
     * Vérifie si l'utilisateur est authentifié.
     * Redirige vers la page de connexion si ce n'est pas le cas.
     */
    private function protectRoutes() {
        $requestUri = strtok($_SERVER["REQUEST_URI"], '?');
        // Si la session admin n'existe pas ET que l'on n'est pas sur la page de login
        if (!isset($_SESSION['is_admin']) && $requestUri !== '/admin/login') {
            header('Location: /admin/login');
            exit;
        }
    }

    /**
     * Affiche et traite le formulaire de connexion.
     */
    public function login() {
        $error = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if ($username === $this->adminConfig['username'] && password_verify($password, $this->adminConfig['password_hash'])) {
                $_SESSION['is_admin'] = true;
                header('Location: /admin');
                exit;
            } else {
                $error = "Identifiants incorrects.";
            }
        }
        // Afficher la vue du formulaire de connexion
        require __DIR__ . '/../Views/admin/login.php';
    }

    /**
     * Déconnecte l'administrateur.
     */
    public function logout() {
        session_unset();
        session_destroy();
        header('Location: /admin/login');
        exit;
    }

    /**
     * Affiche le tableau de bord principal avec la liste des jeux.
     */
    public function dashboard() {
        $games = $this->gameModel->getAllGames();
        require __DIR__ . '/../Views/admin/dashboard.php';
    }

    /**
     * Affiche le formulaire de création de jeu.
     */
    public function create() {
        require __DIR__ . '/../Views/admin/form.php';
    }

    /**
     * Traite la soumission du formulaire de création.
     */
    public function store() {
        $data = $_POST;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($_FILES['image']);
            if ($imagePath) {
                $data['image_url'] = $imagePath;
            }
        }
        $this->gameModel->create($data);
        header('Location: /admin');
        exit;
    }

    /**
     * Affiche le formulaire pour modifier un jeu.
     */
    public function edit() {
        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) { http_response_code(400); echo "ID manquant."; exit; }

        $game = $this->gameModel->find($id);
        if (!$game) { http_response_code(404); echo "Jeu non trouvé"; exit; }
        
        require __DIR__ . '/../Views/admin/form.php';
    }

    /**
     * Traite la soumission du formulaire de modification.
     */
    public function update() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) { http_response_code(400); echo "ID manquant."; exit; }

        $data = $_POST;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $imagePath = $this->handleImageUpload($_FILES['image']);
            if ($imagePath) {
                // TODO: Supprimer l'ancienne image du disque pour éviter les fichiers orphelins
                $data['image_url'] = $imagePath;
            }
        }
        $this->gameModel->update($id, $data);
        header('Location: /admin');
        exit;
    }

    /**
     * Traite la demande de suppression d'un jeu.
     */
    public function delete() {
        $id = (int)($_POST['id'] ?? 0);
        if ($id === 0) { http_response_code(400); echo "ID manquant."; exit; }

        $this->gameModel->delete($id);
        header('Location: /admin');
        exit;
    }
    
    /**
     * Gère la validation et le déplacement d'un fichier image uploadé.
     * @param array $file Le tableau de fichier issu de $_FILES.
     * @return string|null Le chemin d'accès web de l'image ou null si échec.
     */
    private function handleImageUpload($file) {
        $uploadDir = __DIR__ . '/../../public/images/games/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        $fileType = mime_content_type($file['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            return null; // Type de fichier non autorisé
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = uniqid('game_', true) . '.' . $extension;
        $uploadPath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            return '/images/games/' . $fileName;
        }
        
        return null;
    }
}
