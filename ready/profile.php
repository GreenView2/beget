<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$bookings = $pdo->prepare("
    SELECT b.*, s.title as service_title, m.name as master_name, m.photo_url as master_photo
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    LEFT JOIN masters m ON b.master_id = m.id
    WHERE b.client_id = ?
    ORDER BY b.booking_date DESC, b.booking_time ASC
");
$bookings->execute([$user_id]);
$my_bookings = $bookings->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Личный кабинет</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-success bg-opacity-10">

<nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php">✂️ BARBERSHOP</a>
        <div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">На главную</a>
            <a href="change_password.php" class="btn btn-outline-primary btn-sm">Смена пароля</a>
            <a href="logout.php" class="btn btn-dark btn-sm">Выйти</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm rounded-4">
                <div class="card-header bg-success text-white rounded-top-4">
                    <h4 class="mb-0">Мои записи</h4>
                </div>
                <div class="card-body">
                    <?php if (count($my_bookings) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr><th>Дата</th><th>Время</th><th>Мастер</th><th>Услуга</th><th>Статус</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($my_bookings as $b): ?>
                                    <tr>
                                        <td><?= date('d.m.Y', strtotime($b['booking_date'])) ?></td>
                                        <td><?= $b['booking_time'] ?></td>
                                        <td><?= h($b['master_name']) ?></td>
                                        <td><?= h($b['service_title']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= 
                                                $b['status'] == 'pending' ? 'warning' : 
                                                ($b['status'] == 'confirmed' ? 'info' : 
                                                ($b['status'] == 'completed' ? 'success' : 'secondary')) ?>">
                                                <?= $b['status'] ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <h5 class="text-muted">У вас пока нет записей</h5>
                            <a href="index.php#services" class="btn btn-success mt-3 rounded-pill">Записаться</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>