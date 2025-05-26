<?php
require_once 'admin_auth.php';

if (!empty($_SESSION['admin_auth_error'])) {
    $error = $_SESSION['admin_auth_error'];
    unset($_SESSION['admin_auth_error']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Вход для администратора</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-login-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 80vh;
        }
        
        .admin-login-form {
            background-color: #333;
            padding: 30px;
            border-radius: 10px;
            width: 300px;
            text-align: center;
        }
        
        .admin-login-form input {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border-radius: 5px;
            border: none;
            background-color: #ddd;
            color: #333;
        }
        
        .admin-login-form button {
            background-color: #6a5acd;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }
        
        .admin-error {
            color: #ff6b6b;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-login-container">
        <?php if (!empty($error)): ?>
            <div class="admin-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form class="admin-login-form" method="POST" action="admin_auth.php">
            <h2>Вход для администратора</h2>
            <input type="text" name="login" placeholder="Логин" required>
            <input type="password" name="password" placeholder="Пароль" required>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>