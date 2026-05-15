<?php
// logout.php
require_once 'backend/auth.php';
admin_logout();
header('Location: login.php');
exit();
?>