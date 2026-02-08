<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin'){
    die("Доступ запрещен. У вас нет прав администратора. <a href='login.php'>Войти</a>");
}
?>