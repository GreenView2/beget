<?php
require 'check_admin.php';
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
        $booking_id = (int)$_POST['booking_id'];
        $status = $_POST['status'];
        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $booking_id]);
    }
}

$bookings = $pdo->query("
    SELECT b.*, s.title as service_title, s.price, m.name as master_name 
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN masters m ON b.master_id = m.id
    ORDER BY b.booking_date DESC, b.booking_time ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление записями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-success">Управление записями</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">← Назад</a>
    </div>
    
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th><th>Клиент</th><th>Телефон</th><th>Мастер</th>
                        <th>Услуга</th><th>Цена</th><th>Дата</th><th>Время</th><th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                    <tr>
                        <td><?= $b['id'] ?></td>
                        <td><?= h($b['client_name']) ?></td>
                        <td><?= h($b['client_phone']) ?></td>
                        <td><?= h($b['master_name']) ?></td>
                        <td><?= h($b['service_title']) ?></td>
                        <td><?= number_format($b['price'], 0, '', ' ') ?> ₽</td>
                        <td><?= date('d.m.Y', strtotime($b['booking_date'])) ?></td>
                        <td><?= $b['booking_time'] ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <select name="status" class="form-select form-select-sm" style="width: 120px;" onchange="this.form.submit()">
                                    <option value="pending" <?= $b['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                    <option value="confirmed" <?= $b['status'] == 'confirmed' ? 'selected' : '' ?>>Подтвержден</option>
                                    <option value="completed" <?= $b['status'] == 'completed' ? 'selected' : '' ?>>Выполнен</option>
                                    <option value="cancelled" <?= $b['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>