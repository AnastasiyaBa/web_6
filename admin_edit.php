<?php
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

// Получаем ID пользователя для редактирования
$userId = $_SESSION['edit_user_id'] ?? 0;
if (!$userId) {
    $_SESSION['admin_error'] = 'Не выбран пользователь для редактирования';
    header('Location: admin.php');
    exit;
}

// Загружаем данные пользователя
$userData = [];
$userLanguages = [];

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        $_SESSION['admin_error'] = 'Пользователь не найден';
        header('Location: admin.php');
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT language_name FROM user_languages WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userLanguages = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $_SESSION['admin_error'] = 'Ошибка загрузки данных пользователя';
    header('Location: admin.php');
    exit;
}

// Загружаем список всех языков
$languages = [];
$stmt = $pdo->query("SELECT name FROM languages ORDER BY name");
$languages = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    
    // Валидация данных (аналогично process.php)
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $birth_date = $_POST['birth_date'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $bio = trim($_POST['bio'] ?? '');
    $languages = $_POST['languages'] ?? [];
    $contract_agreed = isset($_POST['contract']) && $_POST['contract'] === 'on' ? 1 : 0;
    
    // Проверки валидации (можно вынести в отдельную функцию)
    if (empty($full_name)) $errors[] = 'ФИО обязательно для заполнения';
    if (empty($phone)) $errors[] = 'Телефон обязателен для заполнения';
    if (empty($email)) $errors[] = 'Email обязателен для заполнения';
    if (empty($birth_date)) $errors[] = 'Укажите дату рождения';
    if (!in_array($gender, ['male', 'female'])) $errors[] = 'Укажите пол';
    if (empty($languages)) $errors[] = 'Выберите хотя бы один язык программирования';
    if (!$contract_agreed) $errors[] = 'Необходимо принять условия соглашения';
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Обновляем данные пользователя
            $stmt = $pdo->prepare("
                UPDATE users SET 
                full_name = ?, 
                phone = ?, 
                email = ?, 
                birth_date = ?, 
                gender = ?, 
                bio = ?, 
                contract_agreed = ? 
                WHERE id = ?
            ");
            $stmt->execute([
                $full_name, 
                $phone, 
                $email, 
                $birth_date, 
                $gender, 
                $bio, 
                $contract_agreed, 
                $userId
            ]);
            
            // Удаляем старые языки пользователя
            $stmt = $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?");
            $stmt->execute([$userId]);
            
            // Добавляем новые языки
            $stmt = $pdo->prepare("INSERT INTO user_languages (user_id, language_name) VALUES (?, ?)");
            foreach ($languages as $lang_name) {
                $stmt->execute([$userId, $lang_name]);
            }
            
            $pdo->commit();
            
            $_SESSION['admin_message'] = 'Данные пользователя успешно обновлены';
            unset($_SESSION['edit_user_id']);
            header('Location: admin.php');
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Ошибка при обновлении данных: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Редактирование пользователя</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-edit-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-edit-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .admin-edit-back {
            color: antiquewhite;
            text-decoration: none;
        }
        
        .admin-edit-back:hover {
            text-decoration: underline;
        }
        
        .admin-edit-form {
            background-color: #333;
            padding: 20px;
            border-radius: 10px;
        }
        
        .admin-edit-form label {
            display: block;
            margin-bottom: 5px;
            color: antiquewhite;
        }
        
        .admin-edit-form input[type="text"],
        .admin-edit-form input[type="tel"],
        .admin-edit-form input[type="email"],
        .admin-edit-form input[type="date"],
        .admin-edit-form textarea,
        .admin-edit-form select {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: none;
            background-color: #ddd;
            color: #333;
        }
        
        .admin-edit-form select[multiple] {
            height: 120px;
        }
        
        .admin-edit-form .radio-group {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .admin-edit-form .checkbox {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 15px;
        }
        
        .admin-edit-form button {
            background-color: #6a5acd;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .admin-edit-error {
            color: #f44336;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-edit-container">
        <div class="admin-edit-header">
            <h1>Редактирование пользователя</h1>
            <a href="admin.php" class="admin-edit-back">Назад</a>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="admin-edit-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form class="admin-edit-form" method="POST">
            <label>ФИО:</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($userData['full_name']) ?>" required>
            
            <label>Телефон:</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($userData['phone']) ?>" required>
            
            <label>Email:</label>
            <input type="email" name="email" value="<?= htmlspecialchars($userData['email']) ?>" required>
            
            <label>Дата рождения:</label>
            <input type="date" name="birth_date" value="<?= htmlspecialchars($userData['birth_date']) ?>" required>
            
            <label>Пол:</label>
            <div class="radio-group">
                <label>
                    <input type="radio" name="gender" value="male" <?= $userData['gender'] === 'male' ? 'checked' : '' ?> required>
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" <?= $userData['gender'] === 'female' ? 'checked' : '' ?>>
                    Женский
                </label>
            </div>
            
            <label>Любимые языки программирования:</label>
            <select name="languages[]" multiple size="5" required>
                <?php foreach ($languages as $lang_name): ?>
                    <option value="<?= htmlspecialchars($lang_name) ?>" <?= in_array($lang_name, $userLanguages) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lang_name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <label>Биография:</label>
            <textarea name="bio"><?= htmlspecialchars($userData['bio']) ?></textarea>
            
            <label class="checkbox">
                <input type="checkbox" name="contract" <?= $userData['contract_agreed'] ? 'checked' : '' ?> required>
                С контрактом ознакомлен(а)
            </label>
            
            <button type="submit">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>