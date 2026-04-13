<?php
session_start();
require 'db.php';

$services = $pdo->query("SELECT * FROM services WHERE 1 ORDER BY id")->fetchAll();
$masters = $pdo->query("SELECT * FROM masters WHERE is_active = 1")->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarberShop — Стильная парикмахерская</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-success bg-opacity-10">

<nav class="navbar navbar-expand-lg bg-white shadow-sm fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php">✂️ BARBERSHOP</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
                <li class="nav-item"><a class="nav-link text-dark" href="#services">Услуги</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="#masters">Мастера</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="#contact">Контакты</a></li>
                <li class="nav-item"><a class="nav-link text-dark" href="search_service.php"><i class="fas fa-search"></i> Поиск</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                        <li><a href="admin/admin_panel.php" class="btn btn-outline-danger btn-sm">Админка</a></li>
                    <?php endif; ?>
                    <?php if ($_SESSION['user_role'] === 'master'): ?>
                        <li><a href="master_panel.php" class="btn btn-outline-info btn-sm">Панель мастера</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php" class="btn btn-outline-primary btn-sm">Профиль</a></li>
                    <li><a href="logout.php" class="btn btn-dark btn-sm">Выйти</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="btn btn-success btn-sm">Войти</a></li>
                    <li><a href="register.php" class="btn btn-outline-success btn-sm">Регистрация</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<section class="bg-success bg-opacity-25 pt-5 pb-5 mt-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 text-center text-lg-start">
                <h1 class="display-4 fw-bold text-success">Стиль начинается здесь</h1>
                <p class="lead text-secondary mt-3">Профессиональные стрижки, уход и стиль. Атмосфера уюта и натуральной красоты.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#services" class="btn btn-success btn-lg rounded-pill px-4">Выбрать услугу</a>
                    <a href="search_service.php" class="btn btn-outline-success btn-lg rounded-pill px-4"><i class="fas fa-search"></i> Поиск</a>
                </div>
            </div>
            <div class="col-lg-6 text-center mt-4 mt-lg-0">
                <img src="img/hero.jpg" alt="Салон" class="img-fluid rounded-4 shadow" onerror="this.src='https://via.placeholder.com/500x400?text=Barber+Salon'">
            </div>
        </div>
    </div>
</section>

<section id="services" class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-success bg-opacity-25 text-success px-3 py-2 rounded-pill">Что мы предлагаем</span>
            <h2 class="display-6 fw-bold text-dark mt-2">Наши услуги</h2>
            <p class="text-secondary">Нажмите на услугу для записи</p>
        </div>
        <div class="row g-4">
            <?php foreach ($services as $s): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4">
                    <img src="<?= h($s['image_url']) ?>" class="card-img-top rounded-top-4" style="height: 200px; object-fit: cover;" alt="<?= h($s['title']) ?>">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?= h($s['title']) ?></h5>
                        <p class="card-text text-secondary"><?= h($s['descriptions']) ?></p>
                        <p class="text-success fw-bold fs-4"><?= number_format($s['price'], 0, '', ' ') ?> ₽</p>
                        <p class="text-muted small"><i class="far fa-clock"></i> <?= $s['duration'] ?> мин</p>
                        <!-- Кнопка передает ID услуги -->
                        <a href="booking.php?service_id=<?= $s['id'] ?>" class="btn btn-success rounded-pill px-4 w-100">
                            <i class="fas fa-scissors"></i> Выбрать эту услугу
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Мастера -->
<section id="masters" class="bg-white py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-success bg-opacity-25 text-success px-3 py-2 rounded-pill">Профессионалы</span>
            <h2 class="display-6 fw-bold text-dark mt-2">Наши мастера</h2>
            <p class="text-secondary">Руки, которым можно доверять</p>
        </div>
        <div class="row g-4">
            <?php foreach ($masters as $m): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 text-center h-100">
                    <img src="<?= h($m['photo_url']) ?>" class="card-img-top rounded-top-4" style="height: 250px; object-fit: cover;" alt="<?= h($m['name']) ?>">
                    <div class="card-body">
                        <h5 class="card-title fw-bold"><?= h($m['name']) ?></h5>
                        <p class="text-success fw-semibold"><?= h($m['specialty']) ?></p>
                        <p class="text-secondary small"><?= h($m['experience']) ?></p>
                        <a href="booking.php?master_id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-3">Записаться к этому мастеру</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section id="contact" class="bg-success bg-opacity-10 py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <h3 class="fw-bold text-success">Контакты</h3>
                <p class="text-secondary mt-3"><i class="fas fa-map-marker-alt text-success me-2"></i> г. Москва, ул. Зеленая, 15</p>
                <p class="text-secondary"><i class="fas fa-phone text-success me-2"></i> +7 (999) 123-45-67</p>
                <p class="text-secondary"><i class="fas fa-envelope text-success me-2"></i> hello@barbershop.ru</p>
                <div class="mt-4">
                    <a href="#" class="text-success me-3 fs-5"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-success me-3 fs-5"><i class="fab fa-telegram"></i></a>
                    <a href="#" class="text-success fs-5"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="bg-white p-4 rounded-4 shadow-sm">
                    <h5 class="fw-bold">Быстрая запись</h5>
                    <form action="booking.php" method="GET">
                        <div class="row g-3 mt-2">
                            <div class="col-md-6">
                                <select name="service_id" class="form-select rounded-pill" required>
                                    <option value="">Выберите услугу</option>
                                    <?php foreach ($services as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= h($s['title']) ?> — <?= $s['price'] ?> ₽</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <select name="master_id" class="form-select rounded-pill">
                                    <option value="">Любой мастер</option>
                                    <?php foreach ($masters as $m): ?>
                                        <option value="<?= $m['id'] ?>"><?= h($m['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success rounded-pill w-100">Перейти к оформлению</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="bg-dark text-white-50 text-center py-4">
    <div class="container">
        <p class="mb-0">© 2025 BARBERSHOP. Стиль и гармония.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>