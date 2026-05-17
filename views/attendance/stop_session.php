<?php
ob_start();
require_once __DIR__ . '/../layout.php';

if (!isset($_POST['id'])) {
    $_SESSION['flash_error'] = 'No session ID provided.';
    header('Location: attendance_sessions.php');
    exit;
}

$session_id = (int) $_POST['id'];

try {
    $permissionStmt = $pdo->prepare("SELECT id, department_id, session_name FROM attendance_sessions WHERE id = ?");
    $permissionStmt->execute([$session_id]);
    $session = $permissionStmt->fetch();

    if (!$session) {
        $_SESSION['flash_error'] = 'Session not found.';
        header('Location: attendance_sessions.php');
        ob_end_flush();
        exit;
    }

    if (!is_super_admin() && $session['department_id'] !== null && $session['department_id'] != $_SESSION['department_id']) {
        $_SESSION['flash_error'] = 'You are not authorized to stop this session.';
        header('Location: attendance_sessions.php');
        ob_end_flush();
        exit;
    }

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
        $_SESSION['flash'] = "Session \"{$session['session_name']}\" has been stopped.";
    }

} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Database error: ' . $e->getMessage();
}

header('Location: attendance_sessions.php');
exit;