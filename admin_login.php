<?php
require_once 'admin_auth.php';

// После успешной аутентификации перенаправляем на admin.php
header('Location: admin.php');
exit;
?>