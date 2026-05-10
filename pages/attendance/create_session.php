<?php
// create_session.php
require_once '../../config/database.php';
require_once '../../includes/header.php';

$page_title = 'Create Attendance Session';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_name = $_POST['session_name'] ?? '';
    $attendance_type = $_POST['attendance_type'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO attendance_sessions (session_name, attendance_type, start_time, end_time, status) VALUES (?, ?, ?, ?, 'INACTIVE')");
        $stmt->execute([$session_name, $attendance_type, $start_time, $end_time]);

        header('Location: attendance_sessions.php');
        exit;
    } catch (PDOException $e) {
        $error = $e->getMessage();
    }
}
?>

<div class="container">
    <h1 class="mt-4">Create Attendance Session</h1>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">Error: <?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label for="session_name" class="form-label">Session Name</label>
            <input type="text" class="form-control" id="session_name" name="session_name" required>
        </div>
        <div class="mb-3">
            <label for="attendance_type" class="form-label">Attendance Type</label>
            <select class="form-select" id="attendance_type" name="attendance_type" required>
                <option value="IN">IN</option>
                <option value="OUT">OUT</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="start_time" class="form-label">Start Time</label>
            <input type="time" class="form-control" id="start_time" name="start_time" required>
        </div>
        <div class="mb-3">
            <label for="end_time" class="form-label">End Time</label>
            <input type="time" class="form-control" id="end_time" name="end_time" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Session</button>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>