<?php
$new_password = '1234';
echo password_hash($new_password, PASSWORD_BCRYPT);
?>