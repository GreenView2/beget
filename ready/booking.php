<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
require 'db.php';

$service_id = isset($_GET['service_id']) ? (int)$_GET['service_id'] : (isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0);
$master_id = isset($_GET['master_id']) ? (int)$_GET['master_id'] : (isset($_POST['master_id']) ? (int)$_POST['master_id'] : 0);
$selected_date = isset($_POST['booking_date']) ? $_POST['booking_date'] : (isset($_GET['date']) ? $_GET['date'] : date('Y-m-d'));
$selected_time = isset($_POST['booking_time']) ? $_POST['booking_time'] : '';

$error = '';
$success = '';

if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_booked_slots') {
    header('Content-Type: application/json');
    $master_id_ajax = (int)$_GET['master_id'];
    $date_ajax = $_GET['date'];
    
    $stmt = $pdo->prepare("SELECT booking_time FROM bookings WHERE master_id = ? AND booking_date = ? AND status != 'cancelled'");
    $stmt->execute([$master_id_ajax, $date_ajax]);
    $bookedFromDB = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Обрезаем секунды (было "09:00:00" -> стало "09:00")
    $booked = [];
    foreach ($bookedFromDB as $time) {
        $booked[] = substr($time, 0, 5);
    }
    
    echo json_encode(['booked_slots' => $booked]);
    exit;
}

$service = null;
if ($service_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();
}

$masters = $pdo->query("SELECT * FROM masters WHERE is_active = 1 ORDER BY name")->fetchAll();

function getTimeSlots() {
    $slots = [];
    $start = strtotime('09:00');
    $end = strtotime('19:00');
    for ($time = $start; $time <= $end; $time += 1800) {
        $slots[] = date('H:i', $time);
    }
    return $slots;
}
$allTimeSlots = getTimeSlots();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности. Попробуйте еще раз.";
    } else {
        $client_name = trim($_POST['client_name']);
        $client_phone = trim($_POST['client_phone']);
        $master_id = (int)$_POST['master_id'];
        $service_id = (int)$_POST['service_id'];
        $booking_date = $_POST['booking_date'];
        $booking_time = $_POST['booking_time'];
        $comment = trim($_POST['comment'] ?? '');
        
        if (empty($client_name) || empty($client_phone) || empty($booking_date) || empty($booking_time)) {
            $error = "Заполните все обязательные поля!";
        } elseif (empty($master_id)) {
            $error = "Выберите мастера!";
        } elseif (empty($service_id)) {
            $error = "Выберите услугу!";
        } else {
            // Проверка, что слот не занят
            $stmt = $pdo->prepare("SELECT id FROM bookings WHERE master_id = ? AND booking_date = ? AND booking_time = ? AND status != 'cancelled'");
            $stmt->execute([$master_id, $booking_date, $booking_time]);
            if ($stmt->fetch()) {
                $error = "Это время уже занято. Выберите другое.";
            } else {
                $client_id = $_SESSION['user_id'] ?? null;
                
                $sql = "INSERT INTO bookings (client_id, master_id, service_id, booking_date, booking_time, status, client_name, client_phone, comment) 
                        VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                
                if ($stmt->execute([$client_id, $master_id, $service_id, $booking_date, $booking_time, $client_name, $client_phone, $comment])) {
                    $success = "Запись успешно создана! Мы свяжемся с вами для подтверждения.";
                } else {
                    $error = "Ошибка при создании записи.";
                }
            }
        }
    }
}

$user_name = '';
$user_phone = '';
$user_email = '';
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT first_name, last_name, phone, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $user_name = trim($user['first_name'] . ' ' . $user['last_name']);
        $user_phone = $user['phone'] ?? '';
        $user_email = $user['email'] ?? '';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление записи | BarberShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            min-height: 100vh;
        }
        
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .card-header {
            border-radius: 20px 20px 0 0 !important;
            font-weight: 600;
        }
        
        .scrollable-column {
            max-height: 550px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .scrollable-column::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollable-column::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        
        .scrollable-column::-webkit-scrollbar-thumb {
            background: #2e7d32;
            border-radius: 10px;
        }
        
        /* Карточки мастеров */
        .master-card {
            transition: all 0.2s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .master-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .master-radio:checked + .master-card {
            border-color: #2e7d32;
            background-color: #e8f5e9;
        }
        
        /* Кнопки времени */
        .time-slot-btn {
            transition: all 0.2s ease;
            font-weight: 500;
            border-radius: 12px;
            padding: 8px 0;
            width: 100%;
            text-align: center;
            font-size: 14px;
        }
        
        .time-slot-btn.available {
            background-color: white;
            border: 2px solid #2e7d32;
            color: #2e7d32;
        }
        
        .time-slot-btn.available:hover {
            background-color: #2e7d32;
            color: white;
            transform: scale(1.02);
            cursor: pointer;
        }
        
        .time-slot-btn.booked {
            background-color: #e9ecef !important;
            border: 2px solid #dee2e6 !important;
            color: #6c757d !important;
            cursor: not-allowed !important;
            text-decoration: line-through;
            opacity: 0.8;
        }
        
        .time-slot-btn.selected {
            background-color: #2e7d32;
            border: 2px solid #2e7d32;
            color: white;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #4caf50, #66bb6a);
            border: none;
            border-radius: 50px;
            padding: 14px 50px;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(76,175,80,0.3);
        }
        
        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76,175,80,0.4);
        }
        
        .btn-submit:disabled {
            background: #adb5bd;
            transform: none;
            cursor: not-allowed;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #2e7d32;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .header-btn {
            border-radius: 30px !important;
            padding: 6px 20px !important;
            font-weight: 500;
        }
        
        .debug-info {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold text-success" href="index.php">✂️ BARBERSHOP</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-center gap-2">
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
<div class="container py-4">
    
    <?php if ($success): ?>
        <div class="alert alert-success text-center py-4" style="border-radius: 20px;">
            <i class="fas fa-check-circle fa-3x mb-3"></i>
            <h4><?= $success ?></h4>
            <div class="mt-3">
                <a href="index.php" class="btn btn-success rounded-pill px-4 me-2">На главную</a>
                <a href="profile.php" class="btn btn-outline-success rounded-pill px-4">Мои записи</a>
            </div>
        </div>
    <?php else: ?>
    
    <div class="card bg-white mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <i class="fas fa-cut text-success me-2"></i>
                <strong>Выбранная услуга:</strong>
                <?php if ($service): ?>
                    <span class="ms-2 fw-bold"><?= h($service['title']) ?></span>
                    <span class="badge bg-success ms-2"><?= number_format($service['price'], 0, '', ' ') ?> ₽</span>
                    <span class="badge bg-secondary ms-1"><i class="far fa-clock"></i> <?= $service['duration'] ?> мин</span>
                <?php else: ?>
                    <span class="text-danger ms-2">Не выбрана</span>
                <?php endif; ?>
            </div>
            <a href="index.php#services" class="btn btn-sm btn-outline-success rounded-pill mt-2 mt-md-0">
                <i class="fas fa-exchange-alt me-1"></i> Изменить
            </a>
        </div>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <form method="POST" action="booking.php" id="bookingForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        <input type="hidden" name="service_id" value="<?= $service_id ?>">
        <input type="hidden" name="booking_time" id="selected_time" value="">
        
        <div class="row g-4">
            
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-user me-2"></i> Выберите мастера
                    </div>
                    <div class="card-body scrollable-column">
                        <?php if (empty($masters)): ?>
                            <div class="alert alert-warning">Нет доступных мастеров</div>
                        <?php else: ?>
                            <?php foreach ($masters as $m): ?>
                                <div class="position-relative mb-3">
                                    <input type="radio" name="master_id" 
                                           id="master_<?= $m['id'] ?>" 
                                           value="<?= $m['id'] ?>"
                                           class="master-radio d-none"
                                           <?= ($master_id == $m['id']) ? 'checked' : '' ?>
                                           onchange="onMasterChange(this.value)">
                                    <label for="master_<?= $m['id'] ?>" class="master-card card p-2">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= h($m['photo_url']) ?>" 
                                                 style="width: 55px; height: 55px; object-fit: cover;" 
                                                 class="rounded-circle border">
                                            <div>
                                                <h6 class="mb-0 fw-bold"><?= h($m['name']) ?></h6>
                                                <small class="text-success"><?= h($m['specialty']) ?></small>
                                                <br><small class="text-muted"><?= h($m['experience']) ?></small>
                                            </div>
                                            <?php if ($master_id == $m['id']): ?>
                                                <i class="fas fa-check-circle text-success fs-4 ms-auto"></i>
                                            <?php endif; ?>
                                        </div>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-user-edit me-2"></i> Ваши данные
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Ваше имя *</label>
                            <input type="text" name="client_name" class="form-control rounded-pill" 
                                   value="<?= h($user_name) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Телефон *</label>
                            <input type="tel" name="client_phone" class="form-control rounded-pill" 
                                   value="<?= h($user_phone) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="client_email" class="form-control rounded-pill" 
                                   value="<?= h($user_email) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Комментарий</label>
                            <textarea name="comment" class="form-control" rows="3" style="border-radius: 16px;"></textarea>
                        </div>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-info small">
                                <a href="login.php">Войдите</a> или <a href="register.php">зарегистрируйтесь</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-calendar-alt me-2"></i> Дата и время
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <label class="form-label">Дата записи *</label>
                            <input type="date" name="booking_date" id="booking_date" class="form-control rounded-pill" 
                                   value="<?= $selected_date ?>" 
                                   min="<?= date('Y-m-d') ?>"
                                   onchange="onDateChange()"
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Время записи *</label>
                            <div id="timeSlotsContainer" class="row g-2">
                            </div>
                            <div id="noMasterWarning" class="alert alert-warning small mt-3 <?= $master_id > 0 ? 'd-none' : '' ?>">
                                <i class="fas fa-exclamation-triangle me-1"></i> Сначала выберите мастера
                            </div>
                            <div id="loadingIndicator" class="text-center py-3 d-none">
                                <div class="loading-spinner"></div>
                                <span class="text-muted ms-2">Загрузка...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <button type="submit" id="submitBtn" name="submit_booking" class="btn-submit" disabled>
                <i class="fas fa-check-circle me-2"></i> Подтвердить запись
            </button>
        </div>
        
    </form>
    
    <?php endif; ?>
    
</div>

<script>
let currentMasterId = <?= $master_id ?: 0 ?>;
let currentDate = '<?= $selected_date ?>';
let currentSelectedTime = '';

const allTimeSlots = <?= json_encode($allTimeSlots) ?>;

async function getBookedSlots(masterId, date) {
    if (!masterId || !date) return [];
    
    try {
        const response = await fetch(`booking.php?ajax=get_booked_slots&master_id=${masterId}&date=${date}`);
        const data = await response.json();
        
        const normalizedSlots = (data.booked_slots || []).map(slot => slot.substring(0, 5));
        
        console.log('✅ Занятые слоты для мастера', masterId, 'на дату', date, ':', normalizedSlots);
        return normalizedSlots;
    } catch (error) {
        console.error('❌ Ошибка:', error);
        return [];
    }
}

async function renderTimeSlots() {
    const container = document.getElementById('timeSlotsContainer');
    const warning = document.getElementById('noMasterWarning');
    const loading = document.getElementById('loadingIndicator');

    if (!currentMasterId) {
        container.innerHTML = '<div class="col-12 text-center py-4 text-muted">👈 Выберите мастера</div>';
        warning.classList.remove('d-none');
        return;
    }
    
    warning.classList.add('d-none');
    loading.classList.remove('d-none');
    
    const bookedSlots = await getBookedSlots(currentMasterId, currentDate);
    
    loading.classList.add('d-none');
    container.innerHTML = '';
    
    let bookedCount = 0;
    
    allTimeSlots.forEach(slot => {
        const isBooked = bookedSlots.includes(slot);
        if (isBooked) bookedCount++;
        
        const isSelected = currentSelectedTime === slot;
        
        let btnClass = 'time-slot-btn ';
        if (isBooked) {
            btnClass += 'booked'; 
        } else if (isSelected) {
            btnClass += 'selected'; 
        } else {
            btnClass += 'available'; 
        }
        
        const colDiv = document.createElement('div');
        colDiv.className = 'col-4 mb-2';
        
        const button = document.createElement('button');
        button.type = 'button';
        button.className = btnClass;
        button.textContent = slot;
        
        if (isBooked) {
            button.disabled = true;
            button.innerHTML = slot + ' <i class="fas fa-lock"></i>';
            button.title = 'Это время уже занято';
        } else {
            button.onclick = () => selectTime(slot);
        }
        
        colDiv.appendChild(button);
        container.appendChild(colDiv);
    });
    
    const debugInfo = document.createElement('div');
    debugInfo.className = 'debug-info text-center mt-2';
    debugInfo.innerHTML = `📊 Занятых слотов: ${bookedCount} из ${allTimeSlots.length}`;
    container.appendChild(debugInfo);
    
    console.log(`📊 Создано ${allTimeSlots.length} ячеек, из них занято: ${bookedCount}`);
    
    updateSubmitButton();
}

function selectTime(time) {
    currentSelectedTime = time;
    document.getElementById('selected_time').value = time;
    
    document.querySelectorAll('#timeSlotsContainer .time-slot-btn').forEach(btn => {
        const btnTime = btn.textContent.trim().replace(' 🔒', '');
        if (!btn.disabled) {
            if (btnTime === time) {
                btn.className = 'time-slot-btn selected';
            } else {
                btn.className = 'time-slot-btn available';
            }
        }
    });
    
    updateSubmitButton();
}

function updateSubmitButton() {
    const submitBtn = document.getElementById('submitBtn');
    const hasService = <?= $service_id ? 1 : 0 ?>;
    const hasMaster = currentMasterId > 0;
    const hasDate = currentDate !== '';
    const hasTime = currentSelectedTime !== '';
    
    submitBtn.disabled = !(hasService && hasMaster && hasDate && hasTime);
}

function onMasterChange(masterId) {
    currentMasterId = parseInt(masterId);
    currentSelectedTime = '';
    document.getElementById('selected_time').value = '';
    renderTimeSlots();
}

function onDateChange() {
    const dateInput = document.getElementById('booking_date');
    currentDate = dateInput.value;
    currentSelectedTime = '';
    document.getElementById('selected_time').value = '';
    renderTimeSlots();
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Страница загружена. Мастер:', currentMasterId, 'Дата:', currentDate);
    
    if (currentMasterId > 0 && currentDate) {
        renderTimeSlots();
    } else if (currentMasterId > 0) {
        document.getElementById('timeSlotsContainer').innerHTML = 
            '<div class="col-12 text-center py-4 text-muted">📅 Выберите дату</div>';
    }
});
</script>

</body>
</html>