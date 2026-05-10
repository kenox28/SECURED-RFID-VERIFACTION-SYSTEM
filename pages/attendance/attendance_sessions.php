<?php
// attendance_sessions.php
require_once '../includes/header.php';

$page_title = 'Attendance Sessions';

// Fetch attendance sessions
$stmt = $pdo->query("SELECT * FROM attendance_sessions ORDER BY created_at DESC");
$sessions = $stmt->fetchAll();

?>
<div class="container">
    <h1 class="mt-4">Attendance Sessions</h1>
    <a href="create_session.php" class="btn btn-primary mb-3">Create New Session</a>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Session Name</th>
                <th>Type</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sessions as $session): ?>
                <tr>
                    <td><?php echo htmlspecialchars($session['session_name']); ?></td>
                    <td><?php echo htmlspecialchars($session['attendance_type']); ?></td>
                    <td><?php echo htmlspecialchars($session['start_time']); ?></td>
                    <td><?php echo htmlspecialchars($session['end_time']); ?></td>
                    <td><?php echo htmlspecialchars($session['status']); ?></td>
                    <td>
                        <a href="edit_session.php?id=<?php echo $session['id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                        <a href="delete_session.php?id=<?php echo $session['id']; ?>" class="btn btn-danger btn-sm">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once '../includes/footer.php'; ?>