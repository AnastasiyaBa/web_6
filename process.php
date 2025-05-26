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

// Валидация данных
$errors = [];
$fieldErrors = [];

// ФИО
$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) {
    $errors[] = 'ФИО обязательно для заполнения';
    $fieldErrors['full_name'] = 'Поле обязательно для заполнения';
} elseif (!preg_match('/^[\p{Cyrillic}\p{Latin}\s\-]+$/u', $full_name)) {
    $errors[] = 'ФИО должно содержать только буквы и пробелы';
    $fieldErrors['full_name'] = 'Допустимы только буквы и пробелы';
} elseif (strlen($full_name) > 150) {
    $errors[] = 'ФИО должно быть не длиннее 150 символов';
    $fieldErrors['full_name'] = 'Максимальная длина - 150 символов';
}

// Телефон
$phone = trim($_POST['phone'] ?? '');
if (empty($phone)) {
    $errors[] = 'Телефон обязателен для заполнения';
    $fieldErrors['phone'] = 'Поле обязательно для заполнения';
} elseif (!preg_match('/^\+?[0-9\s\-\(\)]{10,20}$/', $phone)) {
    $errors[] = 'Неверный формат телефона';
    $fieldErrors['phone'] = 'Допустимы цифры, пробелы, скобки и дефисы';
}

// Email
$email = trim($_POST['email'] ?? '');
if (empty($email)) {
    $errors[] = 'Email обязателен для заполнения';
    $fieldErrors['email'] = 'Поле обязательно для заполнения';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Некорректный email';
    $fieldErrors['email'] = 'Пример правильного формата: example@mail.com';
}

// Дата рождения
$birth_date = $_POST['birth_date'] ?? '';
if (empty($birth_date)) {
    $errors[] = 'Укажите дату рождения';
    $fieldErrors['birth_date'] = 'Поле обязательно для заполнения';
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    $errors[] = 'Неверный формат даты';
    $fieldErrors['birth_date'] = 'Используйте формат ГГГГ-ММ-ДД';
}

// Пол
$gender = $_POST['gender'] ?? '';
if (!in_array($gender, ['male', 'female'])) {
    $errors[] = 'Укажите пол';
    $fieldErrors['gender'] = 'Поле обязательно для заполнения';
}

// Языки программирования
$languages = $_POST['languages'] ?? [];
if (empty($languages)) {
    $errors[] = 'Выберите хотя бы один язык программирования';
    $fieldErrors['languages'] = 'Выберите хотя бы один вариант';
} else {
    // Проверяем, что выбранные языки существуют в БД
    $placeholders = implode(',', array_fill(0, count($languages), '?'));
    $stmt = $pdo->prepare("SELECT name FROM languages WHERE name IN ($placeholders)");
    $stmt->execute($languages);
    if ($stmt->rowCount() != count($languages)) {
        $errors[] = 'Выбраны недопустимые языки программирования';
        $fieldErrors['languages'] = 'Недопустимый выбор языков';
    }
}

// Биография
$bio = trim($_POST['bio'] ?? '');
if (!empty($bio) && !preg_match('/^[а-яА-ЯёЁa-zA-Z0-9\s\.,!?\-]+$/u', $bio)) {
    $errors[] = 'Биография содержит недопустимые символы';
    $fieldErrors['bio'] = 'Допустимы буквы, цифры и основные знаки препинания';
}

// Чекбокс соглашения
$contract_agreed = isset($_POST['contract']) && $_POST['contract'] === 'on' ? 1 : 0;
if (!$contract_agreed) {
    $errors[] = 'Необходимо принять условия соглашения';
    $fieldErrors['contract'] = 'Необходимо принять условия';
}

// Если есть ошибки - сохраняем их и возвращаем на форму
if (!empty($errors)) {
    // Сохраняем введенные значения
    foreach ($_POST as $key => $value) {
        if (is_array($value)) {
            setcookie($key, json_encode($value), time() + 3600, '/');
        } else {
            setcookie($key, $value, time() + 3600, '/');
        }
    }
    
    // Сохраняем ошибки
    setcookie('form_errors', json_encode($errors), time() + 3600, '/');
    setcookie('field_errors', json_encode($fieldErrors), time() + 3600, '/');
    
    header('Location: index.php');
    exit;
}

// Если ошибок нет - сохраняем данные в БД
try {
    $pdo->beginTransaction();

    if (isset($_SESSION['user_id'])) {
        // Редактирование существующего пользователя
        $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=?, email=?, birth_date=?, gender=?, bio=?, contract_agreed=? WHERE id=?");
        $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $bio, $contract_agreed, $_SESSION['user_id']]);
        
        // Удаляем старые языки пользователя
        $pdo->prepare("DELETE FROM user_languages WHERE user_id = ?")->execute([$_SESSION['user_id']]);
        
        $user_id = $_SESSION['user_id'];
    } else {
        // Создание нового пользователя
        $login = 'user_' . bin2hex(random_bytes(4));
        $password = bin2hex(random_bytes(4));
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (login, password, full_name, phone, email, birth_date, gender, bio, contract_agreed) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$login, $hashed_password, $full_name, $phone, $email, $birth_date, $gender, $bio, $contract_agreed]);
        
        $user_id = $pdo->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        
        // Сохраняем логин/пароль для показа пользователю
        setcookie('login_info', json_encode(['login'=>$login, 'password'=>$password]), time() + 3600 * 24, '/');
    }

    // Добавляем выбранные языки (используем language_name вместо language_id)
    $stmt = $pdo->prepare("INSERT INTO user_languages (user_id, language_name) VALUES (?, ?)");
    foreach ($languages as $lang_name) {
        $stmt->execute([$user_id, $lang_name]);
    }

    $pdo->commit();
    
    // Сохраняем данные в cookies
    $cookieExpire = time() + 60 * 60 * 24 * 365; // 1 год
    setcookie('full_name', $full_name, $cookieExpire, '/');
    setcookie('phone', $phone, $cookieExpire, '/');
    setcookie('email', $email, $cookieExpire, '/');
    setcookie('birth_date', $birth_date, $cookieExpire, '/');
    setcookie('gender', $gender, $cookieExpire, '/');
    setcookie('languages', json_encode($languages), $cookieExpire, '/');
    setcookie('bio', $bio, $cookieExpire, '/');
    setcookie('contract', 'on', $cookieExpire, '/');
    
    // Удаляем ошибки
    setcookie('form_errors', '', time() - 3600, '/');
    setcookie('field_errors', '', time() - 3600, '/');
    
    // Флаг успешного сохранения
    setcookie('save_success', '1', time() + 3600, '/');
    
    header('Location: index.php');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    
    // Сохраняем ошибку
    setcookie('form_errors', json_encode(['Ошибка сохранения данных: ' . $e->getMessage()]), time() + 3600, '/');
    header('Location: index.php');
    exit;
}