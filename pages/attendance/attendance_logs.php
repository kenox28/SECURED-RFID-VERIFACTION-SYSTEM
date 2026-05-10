<?php
// attendance_logs.php
require_once '../../includes/header.php';
require_once '../../config/database.php';

$page_title = 'Attendance Logs';

// Fetch attendance logs
$stmt = $pdo->query("SELECT al.*, s.first_name, s.last_name, asess.session_name FROM attendance_logs al JOIN students s ON al.student_id = s.id JOIN attendance_sessions asess ON al.session_id = asess.id ORDER BY al.scan_time DESC");
$logs = $stmt->fetchAll();

?>
<div class="container">
    <h1 class="mt-4">Attendance Logs</h1>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Student Name</th>
                <th>RFID UID</th>
                <th>Session</th>
                <th>Type</th>
                <th>Scan Method</th>
                <th>Scan Time</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['rfid_uid']); ?></td>
                    <td><?php echo htmlspecialchars($log['session_name']); ?></td>
                    <td><?php echo htmlspecialchars($log['attendance_type']); ?></td>
                    <td><?php echo htmlspecialchars($log['scan_method']); ?></td>
                    <td><?php echo htmlspecialchars($log['scan_time']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once '../../includes/footer.php'; ?>