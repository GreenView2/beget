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
        $name = trim($_POST['name']);
        $specialty = trim($_POST['specialty']);
        $experience = trim($_POST['experience']);
        $bio = trim($_POST['bio']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $photo_url = $_POST['existing_photo'] ?? '';
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../img/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $filename = 'master_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $uploadFile = $uploadDir . $filename;
            
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadFile)) {
                $photo_url = 'img/' . $filename;
            }
        }
        
        if ($action === 'add') {
            $stmt = $pdo->prepare("INSERT INTO masters (name, specialty, experience, bio, photo_url, phone, email, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $specialty, $experience, $bio, $photo_url ?: 'img/default_master.jpg', $phone, $email, $is_active]);
            $message = "Мастер добавлен!";
        } elseif ($action === 'edit' && $id > 0) {
            $stmt = $pdo->prepare("UPDATE masters SET name=?, specialty=?, experience=?, bio=?, photo_url=?, phone=?, email=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $specialty, $experience, $bio, $photo_url, $phone, $email, $is_active, $id]);
            $message = "Мастер обновлен!";
        }
    }
}

if (isset($_GET['delete']) && isset($_GET['csrf_token']) && $_GET['csrf_token'] === $_SESSION['csrf_token']) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM masters WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: admin_masters.php");
    exit;
}

$masters = $pdo->query("SELECT * FROM masters ORDER BY id")->fetchAll();

$edit_master = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM masters WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit_master = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Управление мастерами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-success">Управление мастерами</h2>
        <a href="admin_panel.php" class="btn btn-outline-secondary">← Назад</a>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5><?= $edit_master ? 'Редактировать' : 'Добавить мастера' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="<?= $edit_master ? 'edit' : 'add' ?>">
                        <?php if ($edit_master): ?>
                            <input type="hidden" name="id" value="<?= $edit_master['id'] ?>">
                            <input type="hidden" name="existing_photo" value="<?= h($edit_master['photo_url']) ?>">
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Имя</label>
                            <input type="text" name="name" class="form-control" required value="<?= $edit_master ? h($edit_master['name']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Специализация</label>
                            <input type="text" name="specialty" class="form-control" value="<?= $edit_master ? h($edit_master['specialty']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Опыт</label>
                            <input type="text" name="experience" class="form-control" value="<?= $edit_master ? h($edit_master['experience']) : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Биография</label>
                            <textarea name="bio" class="form-control" rows="2"><?= $edit_master ? h($edit_master['bio']) : '' ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Телефон</label>
                                <input type="text" name="phone" class="form-control" value="<?= $edit_master ? h($edit_master['phone']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" value="<?= $edit_master ? h($edit_master['email']) : '' ?>">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Фото</label>
                            <?php if ($edit_master && $edit_master['photo_url']): ?>
                                <div class="mb-2"><img src="../<?= h($edit_master['photo_url']) ?>" style="height: 60px;"></div>
                            <?php endif; ?>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" <?= (!$edit_master || $edit_master['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">Активен</label>
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
                            <tr><th>ID</th><th>Имя</th><th>Специализация</th><th>Статус</th><th>Действия</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($masters as $m): ?>
                            <tr>
                                <td><?= $m['id'] ?></td>
                                <td><?= h($m['name']) ?></td>
                                <td><?= h($m['specialty']) ?></td>
                                <td><span class="badge bg-<?= $m['is_active'] ? 'success' : 'secondary' ?>"><?= $m['is_active'] ? 'Активен' : 'Неактивен' ?></span></td>
                                <td>
                                    <a href="?edit=<?= $m['id'] ?>" class="btn btn-sm btn-warning">✏️</a>
                                    <a href="?delete=<?= $m['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Удалить?')">🗑️</a>
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