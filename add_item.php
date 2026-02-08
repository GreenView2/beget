<?php
require '../db.php';
require 'check_admin.php'; // Только для админа!

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $price = $_POST['price'];
    $desc  = trim($_POST['descriptions']);
    $img   = trim($_POST['image_url']);

    if (empty($title)) {
        $message = '<div class="alert alert-danger">Заполните название!</div>';
    } else {
        $sql = "INSERT INTO products (title, descriptions, price, image_url) VALUES (:t, :d, :p, :i)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':t' => $title, ':d' => $desc, ':p' => $price, ':i' => $img]);
        $message = '<div class="alert alert-success">Успешно добавлено!</div>';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1>Новый товар</h1>
        <a href="admin_panel.php" class="btn btn-secondary mb-3">← Назад</a>
        <?= $message ?>
        <form method="POST" class="card p-4">
            <input type="text" name="title" class="form-control mb-2" placeholder="Название" required>
            <input type="number" name="price" class="form-control mb-2" placeholder="Цена" step="0.01">
            <input type="text" name="image_url" class="form-control mb-2" placeholder="URL картинки">
            <textarea name="descriptions" class="form-control mb-2" placeholder="Описание"></textarea>
            <button type="submit" class="btn btn-success">Сохранить</button>
        </form>
    </div>
</body>
</html>