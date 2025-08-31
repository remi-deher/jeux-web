<?php 
// On définit le titre de la page
$title = 'Accueil - Portail de Jeux'; 
// On inclut le header
require __DIR__ . '/layouts/header.php'; 
?>

<header class="page-title">
    <h1> Portail de jeux si tu t'ennuies ;)</h1>
</header>

<div class="main-content">
    <section id="game-list">
        <h2>Nos Jeux</h2>
        <div class="games-container">
            <?php foreach ($games as $game): ?>
                <div class="game-card">
                    <img src="<?= htmlspecialchars($game['image_url']) ?>" alt="<?= htmlspecialchars($game['name']) ?>">
                    <h3><?= htmlspecialchars($game['name']) ?></h3>
                    <p><?= htmlspecialchars($game['description']) ?></p>
                    <div class="game-info">
                        <span class="player-count" data-game-id="<?= $game['id'] ?>">
                            <span class="count">0</span> joueur(s) en ligne
                        </span>
                        <a href="/<?= htmlspecialchars($game['slug']) ?>/" class="play-button">Jouer</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <aside id="chat-section">
        <h2>Chat Général</h2>
        <div id="chat-messages"></div>
        <input type="text" id="chat-input" placeholder="Votre message...">
        <button id="chat-send">Envoyer</button>
    </aside>
</div>

<?php 
// On inclut le footer
require __DIR__ . '/layouts/footer.php'; 
?>
