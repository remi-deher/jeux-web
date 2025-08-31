<?php
// On s'assure que la session est démarrée sur toutes les pages
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Portail de Jeux' ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-container">
        <a href="/" class="nav-brand">Mon Portail de Jeux</a>
        <div class="nav-links">
            </div>
        <div class="nav-admin">
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true): ?>
                <a href="/admin">Dashboard</a>
                <a href="/admin/logout">Déconnexion</a>
            <?php else: ?>
                <a href="/admin/login">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main>
