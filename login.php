<?php
session_start();
require '../db.php';

if ($_SERVER['REQUEST_METHOD'] ==='POST'){
    $email =$_POST['email'];
    $pass = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email =  ? ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])){
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];

        if ($user['role'] === 'admin') {
            header("Location: admin_panel.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        echo "Неверный логин или пароль";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Вход</h4>
                </div>
                <div class="card-body">
                    
                    <!-- Блок вывода сообщений -->
                    <?php if($errorMsg): ?>
                        <div class="alert alert-danger"><?= $errorMsg ?></div>
                    <?php endif; ?>
                    
                    <?php if($successMsg): ?>
                        <div class="alert alert-success"><?= $successMsg ?></div>
                    <?php else: ?>

                    <!-- Сама форма -->
                    <form method="POST" action="login.php">
                        <div class="mb-3">
                            <label class="form-label">Email адрес</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                    <div class="mt-3 text-center">
                        <a href="register.php">Нет аккаунта? Зарегестрироваться</a>
                    </div>

                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>