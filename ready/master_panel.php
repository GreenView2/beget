<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'master') {
    die("Доступ запрещен. <a href='login.php'>Войти</a>");
}

$stmt = $pdo->prepare("SELECT * FROM masters WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$master = $stmt->fetch();

if (!$master) {
    die("Мастер не найден");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    if ($_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'img/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $filename = 'master_' . $master['id'] . '_' . time() . '.' . $ext;
        $uploadFile = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
            $photo_url = 'img/' . $filename;
            $stmt = $pdo->prepare("UPDATE masters SET photo_url = ? WHERE id = ?");
            $stmt->execute([$photo_url, $master['id']]);
            $master['photo_url'] = $photo_url;
            $message = "Фото обновлено!";
        }
    }
}

$bookings = $pdo->prepare("
    SELECT b.*, s.title as service_title 
    FROM bookings b
    LEFT JOIN services s ON b.service_id = s.id
    WHERE b.master_id = ? 
    ORDER BY b.booking_date DESC, b.booking_time ASC
");
$bookings->execute([$master['id']]);
$bookings = $bookings->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель мастера</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-success">Панель мастера - <?= h($master['name']) ?></h2>
        <div>
            <a href="index.php" class="btn btn-outline-secondary">На сайт</a>
            <a href="logout.php" class="btn btn-dark">Выйти</a>
        </div>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <img src="<?= h($master['photo_url']) ?>" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                    <h4><?= h($master['name']) ?></h4>
                    <p class="text-success"><?= h($master['specialty']) ?></p>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <input type="file" name="photo" class="form-control form-control-sm mb-2" accept="image/*" required>
                        <button type="submit" class="btn btn-success btn-sm">Обновить фото</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Мои записи</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Дата</th><th>Время</th><th>Клиент</th><th>Услуга</th><th>Статус</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($b['booking_date'])) ?></td>
                                <td><?= $b['booking_time'] ?></td>
                                <td><?= h($b['client_name']) ?> (<?= h($b['client_phone']) ?>)</td>
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
            </div>
        </div>
    </div>
</div>

</body>
</html>