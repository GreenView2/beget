<?php
require '../db.php';
require 'check_admin.php'; // Только для админа!
header('Content-Type: text/html; charset=utf-8');


$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE products SET title = ?, price = ? WHERE products.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_POST['title'], $_POST['price'], $id]);
    echo "Обновлено!";
}

if (!$product) die("Товар не найден");
?>

<!-- ВАЖНО: В value подставляем старые данные -->
<form method="POST">
    <input type="text" name="title" value="<?= h($product['title']) ?>">
    <input type="number" name="price" value="<?= $product['price'] ?>">
    <button type="submit">Обновить</button>
</form>