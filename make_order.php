<?php
session_start();
require '../db.php';
header('Content-Type: text/html; charset=utf-8');

// 1. Проверка: Вошел ли пользователь?
if (!isset($_SESSION['user_id'])) {
    die("Сначала войдите в систему! <a href='login.php'>Вход</a>");
}

// 2. Получаем ID товара из ссылки (например, make_order.php?id=5)
// (int) — это защита от хакеров, превращаем всё в число
$product_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];

$check = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$check->execute([$product_id]);
$exists = $check->fetch();

if (!$exists) {
    die("Ошибка: Попытка заказать несуществующий товар! Ваш IP записан.");
}

if ($product_id > 0) {
    // 3. Создаем заказ
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, product_id) VALUES (?, ?)");
    try {
        $stmt->execute([$user_id, $product_id]);
        echo "Заказ успешно оформлен! Менеджер свяжется с вами. <a href='index.php'>Вернуться</a>";
    } catch (PDOException $e) {
        echo "Ошибка: " . $e->getMessage();
    }
} else {
    echo "Неверный товар.";
}
?>