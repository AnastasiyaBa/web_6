<?php
session_start();
session_destroy();
setcookie('login_success', '', time() - 3600, '/');
header('Location: index.php');
exit;
?>