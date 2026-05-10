<?php
// logout.php
session_start();

// Log activity before destroying session
if (isset($_SESSION['admin_id'])) {
    require_once 'config/database.php';
    $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)");
    $stmt->execute([$_SESSION['admin_id'], 'Admin logged out']);
}

// Destroy session
session_destroy();

// Clear remember cookie
if (isset($_COOKIE['admin_login'])) {
    setcookie('admin_login', '', time() - 3600, '/');
}

// Redirect to login
header('Location: login.php');
exit();
?>