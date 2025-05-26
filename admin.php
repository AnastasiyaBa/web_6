<?php
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);
session_set_cookie_params(86400);

// Затем запускаем сессию
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'admin_auth.php';
checkAdminAuth();

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

// Обработка действий администратора
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Удаление пользователя
    if (isset($_POST['delete_user'])) {
        $userId = (int)$_POST['user_id'];
        try {
            $pdo->beginTransaction();
            
            // Удаляем языки пользователя
            $stmt = $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Удаляем пользователя
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            $pdo->commit();
            $_SESSION['admin_message'] = 'Пользователь успешно удален';
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['admin_error'] = 'Ошибка при удалении пользователя: ' . $e->getMessage();
        }
    }
    
    // Редактирование пользователя
    if (isset($_POST['edit_user'])) {
        $_SESSION['edit_user_id'] = (int)$_POST['user_id'];
        header('Location: admin_edit.php');
        exit;
    }
    
    header('Location: admin.php');
    exit;
}

// Получение списка всех пользователей
$users = [];
$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Получение статистики по языкам
$languageStats = [];
$stmt = $pdo->query("
    SELECT l.name, COUNT(ul.user_id) as user_count 
    FROM languages l 
    LEFT JOIN user_languages ul ON l.name = ul.language_name 
    GROUP BY l.name 
    ORDER BY user_count DESC
");
$languageStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка сообщений
$message = '';
$error = '';

if (!empty($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

if (!empty($_SESSION['admin_error'])) {
    $error = $_SESSION['admin_error'];
    unset($_SESSION['admin_error']);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Панель администратора</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-logout {
            color: antiquewhite;
            text-decoration: none;
        }
        
        .admin-logout:hover {
            text-decoration: underline;
        }
        
        .admin-section {
            background-color: #333;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .admin-section h2 {
            margin-top: 0;
            color: antiquewhite;
        }
        
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .admin-table th, .admin-table td {
            padding: 10px;
            border: 1px solid #555;
            text-align: left;
        }
        
        .admin-table th {
            background-color: #444;
            color: antiquewhite;
        }
        
        .admin-table tr:nth-child(even) {
            background-color: #3a3a3a;
        }
        
        .admin-table tr:hover {
            background-color: #4a4a4a;
        }
        
        .admin-actions {
            display: flex;
            gap: 10px;
        }
        
        .admin-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            color: white;
            text-decoration: none;
            font-size: 14px;
        }
        
        .admin-btn-edit {
            background-color: #4CAF50;
        }
        
        .admin-btn-delete {
            background-color: #f44336;
        }
        
        .admin-message {
            color: #4CAF50;
            margin-bottom: 15px;
        }
        
        .admin-error {
            color: #f44336;
            margin-bottom: 15px;
        }
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .stat-card {
            background-color: #444;
            padding: 15px;
            border-radius: 5px;
            flex: 1;
            min-width: 200px;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: antiquewhite;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #6a5acd;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Панель администратора</h1>
            <a href="admin_logout.php" class="admin-logout">Выйти</a>
        </div>
        
        <?php if ($message): ?>
            <div class="admin-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="admin-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="admin-section">
            <h2>Статистика по языкам программирования</h2>
            <div class="stats-container">
                <?php foreach ($languageStats as $stat): ?>
                    <div class="stat-card">
                        <h3><?= htmlspecialchars($stat['name']) ?></h3>
                        <div class="stat-value"><?= htmlspecialchars($stat['user_count']) ?></div>
                        <p>пользователей</p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="admin-section">
            <h2>Список пользователей</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ФИО</th>
                        <th>Email</th>
                        <th>Телефон</th>
                        <th>Дата рождения</th>
                        <th>Пол</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['full_name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['phone']) ?></td>
                            <td><?= htmlspecialchars($user['birth_date']) ?></td>
                            <td><?= $user['gender'] === 'male' ? 'Мужской' : 'Женский' ?></td>
                            <td class="admin-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="edit_user" class="admin-btn admin-btn-edit">Редактировать</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" name="delete_user" class="admin-btn admin-btn-delete" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?')">Удалить</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>