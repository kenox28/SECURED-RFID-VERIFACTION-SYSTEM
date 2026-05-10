<?php
// stop_session.php
require_once '../../config/database.php';

if (!isset($_GET['id'])) {
    die('Session ID is required');
}

$session_id = $_GET['id'];

try {
    // Deactivate the selected session
    $stmt = $pdo->prepare("UPDATE attendance_sessions SET status = 'INACTIVE' WHERE id = ?");
    $stmt->execute([$session_id]);

    header('Location: attendance_sessions.php');
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}