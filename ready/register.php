<?php
session_start();
require 'db.php';

$errorMsg = '';
$successMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $pass = $_POST['password'];
    $passConfirm = $_POST['password_confirm'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);

    if (empty($email) || empty($pass) || empty($first_name) || empty($last_name) || empty($phone)) {
        $errorMsg = "Заполните все поля!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = "Некорректный Email!";
    } elseif ($pass !== $passConfirm) {
        $errorMsg = "Пароли не совпадают!";
    } elseif (strlen($pass) < 6) {
        $errorMsg = "Пароль минимум 6 символов!";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $sql = "INSERT INTO users (email, password_hash, role, last_name, first_name, phone) VALUES (?, ?, 'client', ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute([$email, $hash, $last_name, $first_name, $phone]);
            $successMsg = "Регистрация успешна! <a href='login.php'>Войти</a>";
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errorMsg = "Email уже зарегистрирован.";
            } else {
                $errorMsg = "Ошибка БД";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-success bg-opacity-10">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow rounded-4">
                <div class="card-header bg-success text-white rounded-top-4">
                    <h4 class="mb-0">Регистрация</h4>
                </div>
                <div class="card-body">
                    <?php if($errorMsg): ?>
                        <div class="alert alert-danger"><?= $errorMsg ?></div>
                    <?php endif; ?>
                    <?php if($successMsg): ?>
                        <div class="alert alert-success"><?= $successMsg ?></div>
                    <?php else: ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control rounded-pill" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Имя</label>
                                <input type="text" name="first_name" class="form-control rounded-pill" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Фамилия</label>
                                <input type="text" name="last_name" class="form-control rounded-pill" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон</label>
                            <input type="tel" name="phone" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control rounded-pill" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Подтверждение пароля</label>
                            <input type="password" name="password_confirm" class="form-control rounded-pill" required>
                        </div>
                        <button type="submit" class="btn btn-success w-100 rounded-pill">Зарегистрироваться</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="login.php">Уже есть аккаунт? Войти</a>
                    </div>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>