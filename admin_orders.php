<?php
// Временно включим отображение всех ошибок для отладки
require 'check_admin.php'; // Только админ!
require '../db.php';
header('Content-Type: text/html; charset=utf-8');

// Убедимся, что сессия запущена
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Упрощенная обработка формы обновления даты
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Проверяем, что это обновление даты
    if (isset($_POST['update_date'])) {
        
        // Простая проверка CSRF
        if (!isset($_SESSION['csrf_token']) || !isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Ошибка безопасности: неверный CSRF токен');
        }
        
        // Получаем данные
        $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
        $new_date = isset($_POST['execution_date']) ? $_POST['execution_date'] : '';
        
        // Проверяем, что данные не пустые
        if ($order_id > 0 && !empty($new_date)) {
            
            // Пробуем обновить дату
            try {
                $sql = "UPDATE orders SET date = ? WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([$new_date, $order_id]);
                
                if ($result) {
                    // Успешное обновление
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                    exit;
                } else {
                    // Ошибка обновления
                    $error = "Не удалось обновить дату заказа";
                }
            } catch (PDOException $e) {
                // Ошибка базы данных
                $error = "Ошибка БД: " . $e->getMessage();
            }
        } else {
            $error = "Неверные данные: ID заказа или дата не указаны";
        }
    }
}

// Получаем все заказы
try {
    $sql = "
        SELECT 
            orders.id as order_id,
            orders.created_at,
            orders.date,
            users.email,
            users.last_name,
            users.first_name,
            users.phone,
            products.title,
            products.price
        FROM orders
        JOIN users ON orders.user_id = users.id
        JOIN products ON orders.product_id = products.id
        ORDER BY orders.id DESC
    ";
    
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Ошибка при загрузке заказов: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Управление заказами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
    <div class="container">
        <h1>Все заказы</h1>
        <a href="index.php" class="btn btn-secondary mb-3">На главную администратора</a>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Дата заказа успешно обновлена!</div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">Заказов пока нет</div>
        <?php else: ?>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата оформления</th>
                        <th>Email</th>
                        <th>Фамилия</th>
                        <th>Имя</th>
                        <th>Телефон</th>
                        <th>Товар</th>
                        <th>Цена</th>
                        <th>Дата исполнения</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= $order['order_id'] ?></td>
                        <td><?= $order['created_at'] ?></td>
                        <td><?= htmlspecialchars($order['email']) ?></td>
                        <td><?= htmlspecialchars($order['last_name']) ?></td>
                        <td><?= htmlspecialchars($order['first_name']) ?></td>
                        <td><?= htmlspecialchars($order['phone']) ?></td>
                        <td><?= htmlspecialchars($order['title']) ?></td>
                        <td><?= $order['price'] ?> ₽</td>
                        <td>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                <input type="date" name="execution_date" value="<?= htmlspecialchars($order['date']) ?>" class="form-control form-control-sm" style="width: 140px;" required>
                                <button type="submit" name="update_date" class="btn btn-warning btn-sm">Обновить</button>
                            </form>
                        </td>
                        <td>
                            <form action="delete_item.php" method="POST" onsubmit="return confirm('Вы уверены?');">
                                <input type="hidden" name="id" value="<?= $order['order_id'] ?>">
                                <input type="hidden" name="base" value="orders">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Удалить</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>