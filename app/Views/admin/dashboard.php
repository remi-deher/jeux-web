<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
</head>
<body>
    <h1>Gestion des Jeux</h1>
    <a href="/admin/create">Ajouter un jeu</a> | <a href="/admin/logout">Déconnexion</a>
    <hr>
    <table border="1">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Slug</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($games as $game): ?>
            <tr>
                <td><?= $game['id'] ?></td>
                <td><?= htmlspecialchars($game['name']) ?></td>
                <td><?= htmlspecialchars($game['slug']) ?></td>
                <td>
                    <a href="/admin/edit?id=<?= $game['id'] ?>">Modifier</a>
                    <form action="/admin/delete" method="POST" style="display:inline;">
                        <input type="hidden" name="id" value="<?= $game['id'] ?>">
                        <button type="submit" onclick="return confirm('Êtes-vous sûr ?');">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
