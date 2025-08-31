<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portail de Jeux</title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <header>
        <h1>Bienvenue sur notre Portail de Jeux !</h1>
    </header>

    <main>
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
    </main>

    <script src="/js/app.js"></script>
</body>
</html>
