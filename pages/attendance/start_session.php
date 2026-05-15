<?php
// admin/attendance/start_session.php
ob_start();

require_once '../../config/database.php';
session_start();

// Auth guard
if (empty($_SESSION['admin_id'])) {
    header('Location: ../../login.php');
    exit;
}

if (!isset($_POST['id'])) {
    $_SESSION['flash_error'] = 'No session ID provided.';
    header('Location: attendance_sessions.php');
    exit;
}

$session_id = intval($_POST['id']);

try {

    // Deactivate all sessions
    $pdo->exec("UPDATE attendance_sessions SET status='INACTIVE'");

    // Activate selected session
    $stmt = $pdo->prepare("
        UPDATE attendance_sessions
        SET status='ACTIVE'
        WHERE id=?
    ");

    $stmt->execute([$session_id]);

    if ($stmt->rowCount() > 0) {

        $name = $pdo->prepare("
            SELECT session_name
            FROM attendance_sessions
            WHERE id=?
        ");

        $name->execute([$session_id]);

        $sname = $name->fetchColumn();

        $_SESSION['flash'] = "Session \"$sname\" is now ACTIVE.";

    } else {

        $_SESSION['flash_error'] = 'Session not found.';
    }

} catch (PDOException $e) {

    $_SESSION['flash_error'] = 'Database error: ' . $e->getMessage();
}

header('Location: attendance_sessions.php');
ob_end_flush();
exit;
?>