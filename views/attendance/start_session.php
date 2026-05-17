<?php
ob_start();
require_once __DIR__ . '/../layout.php';

if (!isset($_POST['id'])) {
    $_SESSION['flash_error'] = 'No session ID provided.';
    header('Location: attendance_sessions.php');
    exit;
}

$session_id = intval($_POST['id']);

try {
    $permissionStmt = $pdo->prepare("SELECT id, department_id, session_name FROM attendance_sessions WHERE id = ?");
    $permissionStmt->execute([$session_id]);
    $session = $permissionStmt->fetch();

    if (!$session) {
        $_SESSION['flash_error'] = 'Session not found.';
        header('Location: attendance_sessions.php');
        exit;
    }

    if (!is_super_admin()) {
        $allowed = $session['department_id'] === null || $session['department_id'] == $_SESSION['department_id'];
        if (!$allowed) {
            $_SESSION['flash_error'] = 'You are not authorized to start this session.';
            header('Location: attendance_sessions.php');
            exit;
        }

        $inactiveStmt = $pdo->prepare("UPDATE attendance_sessions SET status='INACTIVE' WHERE status='ACTIVE' AND (department_id = ? OR department_id IS NULL)");
        $inactiveStmt->execute([$_SESSION['department_id']]);
    } else {
        $pdo->exec("UPDATE attendance_sessions SET status='INACTIVE'");
    }

    $activateStmt = $pdo->prepare("UPDATE attendance_sessions SET status='ACTIVE' WHERE id = ?");
    $activateStmt->execute([$session_id]);

    if ($activateStmt->rowCount() > 0) {
        $_SESSION['flash'] = "Session \"{$session['session_name']}\" is now ACTIVE.";
    } else {
        $_SESSION['flash_error'] = 'Failed to activate session.';
    }
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Database error: ' . $e->getMessage();
}

header('Location: attendance_sessions.php');
ob_end_flush();
exit;
