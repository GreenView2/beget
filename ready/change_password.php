<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg = "Ошибка безопасности";
    } else {
        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['password_confirm'];
        
        if (strlen($newPassword) < 6) {
            $errorMsg = "Новый пароль должен быть минимум 6 символов";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = "Новые пароли не совпадают";
        } else {
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!password_verify($oldPassword, $user['password_hash'])) {
                $errorMsg = "Старый пароль неверен";
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $stmt->execute([$newHash, $_SESSION['user_id']]);
                $successMsg = "Пароль успешно изменен!";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Смена пароля</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-success bg-opacity-10">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow rounded-4">
                <div class="card-header bg-success text-white rounded-top-4">
                    <h4 class="mb-0">Смена пароля</h4>
                </div>
                <div class="card-body">
                    <?php if($errorMsg): ?>
                        <div class="alert alert-danger"><?= $errorMsg ?></div>
                    <?php endif; ?>
                    <?php if($successMsg): ?>
                        <div class="alert alert-success"><?= $successMsg ?></div>
                        <a href="profile.php" class="btn btn-success">Вернуться в профиль</a>
                    <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Старый пароль</label>
                            <input type="password" name="old_password" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Новый пароль (мин. 6 символов)</label>
                            <input type="password" name="new_password" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Подтверждение пароля</label>
                            <input type="password" name="password_confirm" class="form-control rounded-pill" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 rounded-pill">Изменить пароль</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="profile.php">Вернуться в профиль</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>