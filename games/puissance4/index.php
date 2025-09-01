<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeu du Puissance 4</title>
    <link rel="stylesheet" href="/css/style.css"> 
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="game-main">
        <div id="puissance4-game">
            <h1>Puissance 4</h1>
            <p id="status">En attente d'un autre joueur...</p>
            <div id="board">
                <?php for ($i = 0; $i < 7; $i++): ?>
                    <div class="column" data-column="<?= $i ?>">
                        <?php for ($j = 0; $j < 6; $j++): ?>
                            <div class="slot"></div>
                        <?php endfor; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <div class="game-controls">
                <button id="reset-button" style="display:none;">Rejouer</button>
                <a href="/" class="button-link">Retour au menu</a>
            </div>
        </div>
        <aside id="chat-section">
            <h2>Chat du Jeu</h2>
            <div id="chat-messages"></div>
            <input type="text" id="chat-input" placeholder="Votre message...">
            <button id="chat-send">Envoyer</button>
        </aside>
    </main>
    <script src="/js/app.js"></script>
    <script src="game.js"></script>
</body>
</html>
