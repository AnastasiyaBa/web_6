<?php
$password = 'admin123';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; 
echo password_verify($password, $hash) ? 'OK' : 'Не совпадает';
?>