<?php
require_once 'backend/student_auth.php';
student_logout();
header('Location: student_login.php');
exit();
