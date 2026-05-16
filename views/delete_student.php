<?php
require_once __DIR__ . '/layout.php';

$student_id = $_GET['id'] ?? 0;

if (!$student_id) {
    header('Location: students.php');
    exit();
}

if (is_super_admin()) {
    $stmt = $pdo->prepare("SELECT student_id, photo FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
} else {
    $stmt = $pdo->prepare("SELECT student_id, photo FROM students WHERE id = ? AND department_id = ?");
    $stmt->execute([$student_id, $_SESSION['department_id']]);
}
$student = $stmt->fetch();

if (!$student) {
    $_SESSION['error'] = 'You are not authorized to delete this student.';
    header('Location: students.php');
    exit();
}

try {
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$student_id]);

    if ($student['photo'] && file_exists('../uploads/' . $student['photo'])) {
        unlink('../uploads/' . $student['photo']);
    }

    $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)");
    $stmt->execute([$_SESSION['admin_id'], "Deleted student: {$student['student_id']} "]);

    $_SESSION['success'] = 'Student deleted successfully!';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Failed to delete student: ' . $e->getMessage();
}

header('Location: students.php');
exit();
