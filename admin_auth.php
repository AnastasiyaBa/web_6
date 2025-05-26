<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0); // Скрыть ошибки в продакшене

$host = 'localhost';
$dbname = 'u68917';
$user = 'u68917';
$pass = '1300093';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Проверка авторизации
function checkAdminAuth() {
    if (empty($_SESSION['admin_logged_in'])) {
        $_SESSION['login_redirect'] = $_SERVER['REQUEST_URI'];
        header('Location: admin_login.php');
        exit;
    }
}

// Обработка входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login = trim($_POST['login']);
    $password = trim($_POST['password']);
    
    $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE login = ?");
    $stmt->execute([$login]);
    $admin = $stmt->fetch();
    
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_logged_in'] = true;
        
        // Перенаправление на исходную страницу
        $redirect = $_SESSION['login_redirect'] ?? 'admin.php';
        unset($_SESSION['login_redirect']);
        header("Location: $redirect");
        exit;
    } else {
        $_SESSION['admin_auth_error'] = 'Неверные данные';
        header('Location: admin_login.php');
        exit;
    }
}
?>