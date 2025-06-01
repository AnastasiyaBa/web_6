<?php
$valid_login = "admin";
$valid_password = "1234"; 

// Проверка авторизации
function checkAuth() {
    global $valid_login, $valid_password;
    
    if (!isset($_SERVER['PHP_AUTH_USER'])) {
        header('WWW-Authenticate: Basic realm="Admin Panel"');
        header('HTTP/1.0 401 Unauthorized');
        die("Требуется авторизация");
    }
    
    if ($_SERVER['PHP_AUTH_USER'] !== $valid_login || 
        $_SERVER['PHP_AUTH_PW'] !== $valid_password) {
        header('HTTP/1.0 401 Unauthorized');
        die("Неверные учетные данные");
    }
}

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

// Проверяем авторизацию при каждом запросе
checkAuth();
?>