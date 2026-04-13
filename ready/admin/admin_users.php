<?php
require 'check_admin.php';
require '../db.php';

$users = $pdo->query("SELECT id, email, role, first_name, last_name, phone, created_at FROM users ORDER BY id")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление пользователями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-success">Управление пользователями</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">← Назад</a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>ID</th><th>Email</th><th>Имя</th><th>Телефон</th><th>Роль</th><th>Дата регистрации</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= h($u['email']) ?></td>
                        <td><?= h($u['first_name']) ?> <?= h($u['last_name']) ?></td>
                        <td><?= h($u['phone']) ?></td>
                        <td>
                            <span class="badge bg-<?= $u['role'] == 'admin' ? 'danger' : ($u['role'] == 'master' ? 'info' : 'secondary') ?>">
                                <?= $u['role'] ?>
                            </span>
                        </td>
                        <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>