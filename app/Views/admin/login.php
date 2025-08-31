<!DOCTYPE html>
<html>
<head>
    <title>Connexion Admin</title>
</head>
<body>
    <h1>Connexion</h1>
    <?php if (isset($error)): ?>
        <p style="color:red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form action="/admin/login" method="POST">
        <input type="text" name="username" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>
</body>
</html>
