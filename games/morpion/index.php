<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jeu du Morpion</title>
    <link rel="stylesheet" href="/css/style.css"> 
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <main class="game-main">
        <div id="morpion-game">
            <h1>Morpion en temps réel</h1>
            <p id="status">En attente d'un autre joueur...</p>
            <div id="board">
                <?php for ($i = 0; $i < 9; $i++): ?>
                    <div class="cell" data-index="<?= $i ?>"></div>
                <?php endfor; ?>
            </div>
            <button id="reset-button" style="display:none;">Rejouer</button>
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
