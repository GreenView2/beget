<?php
require 'check_admin.php';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-light">

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-success">👑 Админ-панель</h1>
        <div>
            <a href="../index.php" class="btn btn-outline-secondary">На сайт</a>
            <a href="../logout.php" class="btn btn-dark">Выйти</a>
        </div>
    </div>
    
    <div class="row g-4">
        <div class="col-md-6 col-lg-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-cut fa-3x text-success mb-3"></i>
                    <h5>Услуги</h5>
                    <a href="admin_services.php" class="btn btn-success">Управлять</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>
                    <h5>Мастера</h5>
                    <a href="admin_masters.php" class="btn btn-success">Управлять</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-calendar-check fa-3x text-success mb-3"></i>
                    <h5>Записи</h5>
                    <a href="admin_bookings.php" class="btn btn-success">Управлять</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="card text-center shadow-sm h-100">
                <div class="card-body">
                    <i class="fas fa-user-friends fa-3x text-success mb-3"></i>
                    <h5>Пользователи</h5>
                    <a href="admin_users.php" class="btn btn-success">Управлять</a>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>