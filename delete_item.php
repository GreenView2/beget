<?php
session_start();
require '../db.php';
require 'check_admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Проверка токена
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("CSRF Attack blocked");
    }

    $id = (int)$_POST['id'];
    $base = $_POST['base'];

    $allowed_bases = ['users', 'products', 'orders']; // Ваши реальные таблицы
    if (!in_array($base, $allowed_bases, true)) {
        die("Invalid table name");
    }

    // 2. Удаление
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: index.php");
}
?>