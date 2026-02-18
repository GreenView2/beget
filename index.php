<?php
session_start();
require '../db.php';

// Пагинация
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Получаем общее кол-во товаров (для кнопок 1, 2, 3...)
$total_stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_rows = $total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);

// Получаем данные с пагинацией
$stmt = $pdo->query("SELECT * FROM products ORDER BY id DESC LIMIT $limit OFFSET $offset");
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
                    <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/300') ?>" class="card-img-top" style="height: 200px; object-fit: cover;" alt="<?= h($p['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?= h($p['title']) ?></h5>
                        <p class="card-text"><?= h($p['descriptions']) ?></p>
                        <p class="text-primary fw-bold"><?= $p['price'] ?> ₽</p>
                        <div class="d-flex gap-2 align-items-center">
                            <a href="make_order.php?id=<?= $p['id'] ?>" class="btn btn-primary">Купить</a>
                            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                <a href="edit_item.php?id=<?= $p['id'] ?>" class="btn btn-warning">✏️</a>
                            <?php endif; ?>
                            <form action="delete_item.php" method="POST" onsubmit="return confirm('Вы уверены?');" class="m-0">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <input type="hidden" name="base" value="products">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']?>">
                                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                    <button type="submit" class="btn btn-danger btn-sm">🗑️ Удалить</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<?php if ($total_pages > 1): ?>
<nav class="mt-4">
  <ul class="pagination justify-content-center">
    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
      <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
      </li>
    <?php endfor; ?>
  </ul>
</nav>
<?php endif; ?>
</body>
</html>