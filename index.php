<?php
session_start();
require '../db.php';

// Получаем данные (Сортировка: новые сверху)
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC");
$products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Главная</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-light bg-light px-4 mb-4">
    <span class="navbar-brand">Мой Проект</span>
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="admin_panel.php" class="btn btn-danger btn-sm">Админка</a>
            <?php endif; ?>
            <a href="profile.php" class="btn btn-primary btn-sm">Профиль</a>
            <a href="logout.php" class="btn btn-dark btn-sm">Выйти</a>
        <?php else: ?>
            <a href="login.php" class="btn btn-primary btn-sm">Войти</a>
        <?php endif; ?>
    </div>
</nav>

<div class="container">
    <div class="row">
        <?php foreach ($products as $p): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/300') ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                    <div class="card-body">
                        <h5 class="card-title"><?= h($p['title']) ?></h5>
                        <p class="card-text"><?= h($p['descriptions']) ?></p>
                        <p class="text-primary fw-bold"><?= $p['price'] ?> ₽</p>
                        <a href="make_order.php?id=<?= $p['id'] ?>" class="btn btn-primary">Купить</a>
                    </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>