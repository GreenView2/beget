<?php
require 'check_admin.php';
require '../db.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности";
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        $title = trim($_POST['title']);
        $price = (float)$_POST['price'];
        $duration = (int)$_POST['duration'];
        $descriptions = trim($_POST['descriptions']);
        
        $image_url = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../img/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $filename = 'service_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $uploadFile = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
                $image_url = 'img/' . $filename;
            }
        }
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO services (title, descriptions, price, duration, image_url) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $descriptions, $price, $duration, $image_url ?: 'img/default_service.jpg'])) {
                $message = "Услуга добавлена!";
            }
        } elseif ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE services SET title=?, descriptions=?, price=?, duration=?, image_url=? WHERE id=?");
            if ($stmt->execute([$title, $descriptions, $price, $duration, $image_url, $id])) {
                $message = "Услуга обновлена!";
            }
        }
    }
}

if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_services.php");
    exit;
}

$services = $pdo->query("SELECT * FROM services ORDER BY id")->fetchAll();

$edit_service = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_service = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление услугами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-success">Управление услугами</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">← Назад</a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5><?= $edit_service ? 'Редактировать' : 'Добавить услугу' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="<?= $edit_service ? 'edit' : 'add' ?>">
                        <?php if ($edit_service): ?>
                            <input type="hidden" name="id" value="<?= $edit_service['id'] ?>">
                            <input type="hidden" name="existing_image" value="<?= h($edit_service['image_url']) ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Название</label>
                            <input type="text" name="title" class="form-control" required value="<?= $edit_service ? h($edit_service['title']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Описание</label>
                            <textarea name="descriptions" class="form-control" rows="3"><?= $edit_service ? h($edit_service['descriptions']) : '' ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Цена (₽)</label>
                                <input type="number" name="price" class="form-control" step="0.01" required value="<?= $edit_service ? $edit_service['price'] : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Длительность (мин)</label>
                                <input type="number" name="duration" class="form-control" required value="<?= $edit_service ? $edit_service['duration'] : 30 ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Фото</label>
                            <?php if ($edit_service && $edit_service['image_url']): ?>
                                <div class="mb-2"><img src="../<?= h($edit_service['image_url']) ?>" style="height: 60px;"></div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                        <button type="submit" class="btn btn-success w-100">Сохранить</button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>ID</th><th>Название</th><th>Цена</th><th>Длит.</th><th>Действия</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($services as $s): ?>
                            <tr>
                                <td><?= $s['id'] ?></td>
                                <td><?= h($s['title']) ?></td>
                                <td><?= number_format($s['price'], 0, '', ' ') ?> ₽</td>
                                <td><?= $s['duration'] ?> мин</td>
                                <td>
                                    <a href="?edit=<?= $s['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                                    <a href="?delete=<?= $s['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">🗑️</a>
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