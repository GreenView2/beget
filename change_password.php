<?php
session_start();
require '../db.php';

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверка CSRF-токена
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg = "Ошибка безопасности. Пожалуйста, попробуйте еще раз.";
    } else {
        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['password_confirm'];
        
        // Валидация длины нового пароля
        if (strlen($newPassword) < 8) {
            $errorMsg = "Новый пароль должен содержать минимум 8 символов!";
        } elseif ($newPassword !== $confirmPassword) {
            $errorMsg = "Новые пароли не совпадают!";
        } else {
            // Получаем текущий хеш пароля из базы данных
            $sql = "SELECT password_hash FROM users WHERE id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                $errorMsg = "Пользователь не найден!";
            } elseif (!password_verify($oldPassword, $user['password_hash'])) {
                $errorMsg = "Старый пароль указан неверно!";
            } else {
                // Хешируем новый пароль
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Обновляем пароль в базе данных
                $sql = "UPDATE users SET password_hash = :hash WHERE id = :user_id";
                $stmt = $pdo->prepare($sql);
                
                try {
                    $stmt->execute([
                        ':user_id' => $_SESSION['user_id'],
                        ':hash' => $newHash
                    ]);
                    
                    $successMsg = "Пароль успешно изменен! <a href='profile.php' class='alert-link'>Вернуться в профиль</a>";
                } catch (PDOException $e) {
                    $errorMsg = "Ошибка при обновлении пароля: " . $e->getMessage();
                }
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
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Смена пароля</h4>
                </div>
                <div class="card-body">
                    
                    <!-- Блок вывода сообщений -->
                    <?php if($errorMsg): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($errorMsg) ?></div>
                    <?php endif; ?>
                    
                    <?php if($successMsg): ?>
                        <div class="alert alert-success"><?= $successMsg ?></div>
                    <?php else: ?>

                    <!-- Форма смены пароля -->
                    <form method="POST" action="change_password.php">
                        <!-- CSRF токен -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        
                        <!-- Старый пароль -->
                        <div class="mb-3">
                            <label class="form-label">Старый пароль</label>
                            <input type="password" name="old_password" class="form-control" required minlength="1">
                        </div>
                        
                        <!-- Новый пароль -->
                        <div class="mb-3">
                            <label class="form-label">Новый пароль (минимум 8 символов)</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                        </div>
                        
                        <!-- Подтверждение нового пароля -->
                        <div class="mb-3">
                            <label class="form-label">Повторите новый пароль</label>
                            <input type="password" name="password_confirm" class="form-control" required minlength="8">
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Изменить пароль</button>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="profile.php" class="btn btn-outline-secondary">Вернуться в профиль</a>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>