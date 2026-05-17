<?php
session_start();
require_once __DIR__ . '/../../config/database.php';



if (!isset($_POST['id'])) {
    $_SESSION['flash_error'] = 'No session ID provided.';
    header('Location: attendance_sessions.php');
    exit;
}

$session_id = (int) $_POST['id'];

try {
    // 1. Stop session in database
    $stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'INACTIVE' WHERE id = ?");
    $stmt->execute([$session_id]);

    if ($stmt->rowCount() > 0) {

        // 2. Get session name for message
        $stmt2 = $pdo->prepare("SELECT session_name FROM attendance_sessions WHERE id = ?");
        $stmt2->execute([$session_id]);
        $name = $stmt2->fetchColumn();

        // 3. IMPORTANT: clear any session tracking (FIX)
        unset($_SESSION['active_session']);
        unset($_SESSION['current_session']);
        unset($_SESSION['session_id']);

        // 4. Flash message
        $_SESSION['flash'] = "Session \"$name\" has been STOPPED successfully.";

    } else {
        $_SESSION['flash_error'] = 'Session not found or already inactive.';
    }

} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Database error: ' . $e->getMessage();
}

header('Location: attendance_sessions.php');
exit;