<?php
// logout.php
require_once 'backend/auth.php';
user_logout();
header('Location: login.php');
exit();
?>