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

// Загрузка списка языков из БД
$stmt = $pdo->query("SELECT name FROM languages ORDER BY name");
$languages = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Инициализация переменных
$userData = [];
$userLanguages = [];
$isLoggedIn = !empty($_SESSION['user_id']);

// Загрузка данных пользователя если авторизован
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT language_name FROM user_languages WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $userLanguages = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Обработка сообщений
$messages = [];

if (!empty($_COOKIE['save_success'])) {
    $messages[] = '<div class="success">Данные успешно сохранены</div>';
    setcookie('save_success', '', time() - 3600, '/');
}

if (!empty($_COOKIE['login_success'])) {
    $messages[] = '<div class="success">Вы успешно авторизованы</div>';
    setcookie('login_success', '', time() - 3600, '/');
}

if (!empty($_COOKIE['login_info'])) {
    $loginInfo = json_decode($_COOKIE['login_info'], true);
    $messages[] = '<div class="info">Ваши данные для входа:<br>'
        . 'Логин: <strong>' . htmlspecialchars($loginInfo['login']) . '</strong><br>'
        . 'Пароль: <strong>' . htmlspecialchars($loginInfo['password']) . '</strong></div>';
}

// Загрузка ошибок
if (!empty($_COOKIE['form_errors'])) {
    $formErrors = json_decode($_COOKIE['form_errors'], true);
    foreach ($formErrors as $error) {
        $messages[] = '<div class="error">' . htmlspecialchars($error) . '</div>';
    }
    setcookie('form_errors', '', time() - 3600, '/');
}

// Загрузка ошибок полей
$fieldErrors = [];
if (!empty($_COOKIE['field_errors'])) {
    $fieldErrors = json_decode($_COOKIE['field_errors'], true);
    setcookie('field_errors', '', time() - 3600, '/');
}

// Загрузка сохраненных значений
$formData = [
    'full_name' => '',
    'phone' => '',
    'email' => '',
    'birth_date' => '',
    'gender' => '',
    'bio' => '',
    'contract' => '',
    'languages' => []
];

// Заполняем данные из разных источников
foreach ($formData as $key => $value) {
    if ($isLoggedIn && isset($userData[$key])) {
        $formData[$key] = $userData[$key];
    } elseif (isset($_COOKIE[$key])) {
        $formData[$key] = is_array($value) ? json_decode($_COOKIE[$key], true) : $_COOKIE[$key];
    }
}

// Особый случай для языков
if (!empty($userLanguages)) {
    $formData['languages'] = $userLanguages;
} elseif (!empty($_COOKIE['languages'])) {
    $formData['languages'] = json_decode($_COOKIE['languages'], true);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <title>Анкета пользователя</title>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <style>
        /* Новые стили для кнопок авторизации */
        .auth-buttons {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 10px;
        }
        
        .auth-btn {
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        
        .admin-auth {
            background-color: #d9534f; /* Красный */
        }
        
        .user-auth {
            background-color: #5bc0de; /* Голубой */
        }
        
        .auth-btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <header>
        <h1>Анкета пользователя</h1>
        <!-- Блок с кнопками авторизации -->
        <div class="auth-buttons">
            <a href="admin_login.php" class="auth-btn admin-auth">Вход для администратора</a>
            <a href="login.php" class="auth-btn user-auth">Вход для пользователя</a>
        </div>
        <?php if ($isLoggedIn): ?>
            <div class="auth-info">
                <h2>Вы вошли как <?= htmlspecialchars($formData['full_name']) ?>
                (<a href="logout.php">выйти</a>)</h2>
            </div>
        <?php endif; ?>
    </header>

    <div class="main">
        <?php foreach ($messages as $message): ?>
            <?= $message ?>
        <?php endforeach; ?>

        <form method="POST" action="process.php">
            <!-- ФИО -->
            <label>ФИО:</label>
            <input type="text" name="full_name" 
                   value="<?= htmlspecialchars($formData['full_name']) ?>" 
                   class="<?= !empty($fieldErrors['full_name']) ? 'error-field' : '' ?>" 
                   required>
            <?php if (!empty($fieldErrors['full_name'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['full_name']) ?></div>
            <?php endif; ?>

            <!-- Телефон -->
            <label>Номер телефона:</label>
            <input type="tel" name="phone" 
                   value="<?= htmlspecialchars($formData['phone']) ?>" 
                   class="<?= !empty($fieldErrors['phone']) ? 'error-field' : '' ?>" 
                   required>
            <?php if (!empty($fieldErrors['phone'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['phone']) ?></div>
            <?php endif; ?>

            <!-- Email -->
            <label>Адрес электронной почты:</label>
            <input type="email" name="email" 
                   value="<?= htmlspecialchars($formData['email']) ?>" 
                   class="<?= !empty($fieldErrors['email']) ? 'error-field' : '' ?>" 
                   required>
            <?php if (!empty($fieldErrors['email'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['email']) ?></div>
            <?php endif; ?>

            <!-- Дата рождения -->
            <label>Дата рождения:</label>
            <input type="date" name="birth_date" 
                   value="<?= htmlspecialchars($formData['birth_date']) ?>" 
                   class="<?= !empty($fieldErrors['birth_date']) ? 'error-field' : '' ?>" 
                   required>
            <?php if (!empty($fieldErrors['birth_date'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['birth_date']) ?></div>
            <?php endif; ?>

            <!-- Пол -->
            <label>Пол:</label>
            <div class="radio-group <?= !empty($fieldErrors['gender']) ? 'error-field' : '' ?>">
                <label>
                    <input type="radio" name="gender" value="male" 
                           <?= $formData['gender'] === 'male' ? 'checked' : '' ?> required>
                    Мужской
                </label>
                <label>
                    <input type="radio" name="gender" value="female" 
                           <?= $formData['gender'] === 'female' ? 'checked' : '' ?>>
                    Женский
                </label>
            </div>
            <?php if (!empty($fieldErrors['gender'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['gender']) ?></div>
            <?php endif; ?>

            <!-- Языки программирования -->
            <label>Любимые языки программирования:</label>
            <select name="languages[]" multiple size="5" 
                    class="<?= !empty($fieldErrors['languages']) ? 'error-field' : '' ?>" 
                    required>
                <?php foreach ($languages as $lang_name): ?>
                    <option value="<?= htmlspecialchars($lang_name) ?>" 
                        <?= in_array($lang_name, $formData['languages']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lang_name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($fieldErrors['languages'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['languages']) ?></div>
            <?php endif; ?>

            <!-- Биография -->
            <label>Биография:</label>
            <textarea name="bio"
                      class="<?= !empty($fieldErrors['bio']) ? 'error-field' : '' ?>"><?= 
                htmlspecialchars($formData['bio']) 
            ?></textarea>
            <?php if (!empty($fieldErrors['bio'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['bio']) ?></div>
            <?php endif; ?>

            <!-- Соглашение -->
            <label class="checkbox <?= !empty($fieldErrors['contract']) ? 'error-field' : '' ?>">
                <input type="checkbox" name="contract" 
                       <?= !empty($formData['contract']) ? 'checked' : '' ?> required>
                С контрактом ознакомлен(а)*
            </label>
            <?php if (!empty($fieldErrors['contract'])): ?>
                <div class="field-error"><?= htmlspecialchars($fieldErrors['contract']) ?></div>
            <?php endif; ?>

            <!-- Кнопка отправки -->
            <button type="submit"><?= $isLoggedIn ? 'Обновить данные' : 'Сохранить' ?></button>

            <?php if (!$isLoggedIn): ?>
                <div class="auth-link">
                    Уже регистрировались? <a href="login.php">Войдите</a> для редактирования данных.
                </div>
            <?php endif; ?>
        </form>
    </div>

    <footer>
        <p>Баринова А.В. 27/2</p>
    </footer>
</body>
</html>