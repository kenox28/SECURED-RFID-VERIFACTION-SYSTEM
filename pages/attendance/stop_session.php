<?php
// admin/attendance/stop_session.php
ob_start();
require_once '../../config/database.php';
session_start();

// Auth guard
if (empty($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

// ── BUG FIX: Was $_GET['id'] — form submits via POST so it was always NULL
if (!isset($_POST['id'])) {
    $_SESSION['flash_error'] = 'No session ID provided.';
    header('Location: attendance_sessions.php');
    ob_end_flush();
    exit;
}

$session_id = intval($_POST['id']);

try {
    $stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'INACTIVE' WHERE id = ?");
    $stmt->execute([$session_id]);

    if ($stmt->rowCount() === 0) {
        $_SESSION['flash_error'] = 'Session not found.';
    } else {
        $name = $pdo->prepare("SELECT session_name FROM attendance_sessions WHERE id = ?");
        $name->execute([$session_id]);
        $sname = $name->fetchColumn();
        $_SESSION['flash'] = "Session \"$sname\" has been stopped.";
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Database error: ' . $e->getMessage();
}

header('Location: attendance_sessions.php');
ob_end_flush();
exit;
?>