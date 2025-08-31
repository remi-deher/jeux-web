<!DOCTYPE html>
<html>
<head>
    <title><?= isset($game) ? 'Modifier' : 'Ajouter' ?> un Jeu</title>
</head>
<body>
    <h1><?= isset($game) ? 'Modifier le jeu : ' . htmlspecialchars($game['name']) : 'Ajouter un nouveau jeu' ?></h1>
    
    <form action="<?= isset($game) ? '/admin/update' : '/admin/store' ?>" method="POST" enctype="multipart/form-data">
        <?php if (isset($game)): ?>
            <input type="hidden" name="id" value="<?= $game['id'] ?>">
        <?php endif; ?>
        
        <p>
            <label>Nom :</label><br>
            <input type="text" name="name" value="<?= htmlspecialchars($game['name'] ?? '') ?>" required>
        </p>
        <p>
            <label>Description :</label><br>
            <textarea name="description" required><?= htmlspecialchars($game['description'] ?? '') ?></textarea>
        </p>
        <p>
            <label>Slug (URL) :</label><br>
            <input type="text" name="slug" value="<?= htmlspecialchars($game['slug'] ?? '') ?>" required>
        </p>
        <p>
            <label>Image :</label><br>
            <input type="file" name="image">
            <?php if (isset($game['image_url'])): ?>
                <img src="<?= htmlspecialchars($game['image_url']) ?>" alt="" width="100">
            <?php endif; ?>
        </p>
        <button type="submit">Enregistrer</button>
        <a href="/admin">Annuler</a>
    </form>
</body>
</html>
