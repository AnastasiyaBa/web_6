<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД
$host = 'localhost';
$dbname = 'u68917';
$user = 'u68917';
$pass = '1300093';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            setcookie('login_success', '1', time() + 60, '/');
            header('Location: index.php');
            exit;
        } else {
            setcookie('login_error', 'Неверный логин или пароль', time() + 60, '/');
        }
    } catch (PDOException $e) {
        setcookie('login_error', 'Ошибка авторизации', time() + 60, '/');
    }
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Вход</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Дополнительные стили только для страницы входа */
        .login-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
        }
        
        .login-form {
            background-color: #333;
            padding: 30px;
            border-radius: 10px;
            width: 220px;
            text-align: center;
        }
        
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 200px; /* Уменьшенные поля */
            padding: 8px;
            margin: 5px auto;
            border-radius: 5px;
            border: 2px;
            background-color: #fff;
            background-color: #ddd;
            color: #333;
            display: block;
        }
        
        .login-form button {
            background-color: #6a5acd;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .login-links {
            margin-top: 15px;
        }
        
        .login-links a {
            color: antiquewhite;
            text-decoration: none;
        }
        
        .error {
            color:rgb(0, 0, 0);
            text-align: center;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <?php if (!empty($_COOKIE['login_error'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['login_error']) ?></div>
            <?php setcookie('login_error', '', time() - 3600, '/'); ?>
        <?php endif; ?>
        
        <form class="login-form" method="POST">
            <input type="text" name="login" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
            <div class="login-links">
                <a href="index.php">Назад к форме</a>
            </div>
        </form>
    </div>
</body>
</html>