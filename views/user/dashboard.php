<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';
render_student_header($page_title);

$student = get_logged_student();
$studentId = $student['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM attendance_logs WHERE student_id = ?');
$stmt->execute([$studentId]);
$total_attendance = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM attendance_logs WHERE student_id = ? AND attendance_type = 'IN'");
$stmt->execute([$studentId]);
$present_count = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM attendance_sessions');
$stmt->execute();
$total_sessions = $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(DISTINCT session_id) FROM attendance_logs WHERE student_id = ?');
$stmt->execute([$studentId]);
$attended_sessions = $stmt->fetchColumn();

$absent_count = max(0, $total_sessions - $attended_sessions);

$logStmt = $pdo->prepare('SELECT al.attendance_type, al.scan_time, asess.session_name, al.scan_method FROM attendance_logs al JOIN attendance_sessions asess ON al.session_id = asess.id WHERE al.student_id = ? ORDER BY al.scan_time DESC LIMIT 5');
$logStmt->execute([$studentId]);
$recent_logs = $logStmt->fetchAll();
?>

<style>
    .dashboard-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:1rem; margin-bottom:1.5rem; }
    .dashboard-card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.5rem; box-shadow:0 8px 24px rgba(15,23,42,0.04); }
    .dashboard-card h3 { margin:0 0 0.75rem; font-size:1rem; color:#0f172a; }
    .dashboard-card .value { font-size:2.25rem; font-weight:700; color:#0f172a; }
    .dashboard-card .note { color:#64748b; margin-top:0.5rem; }
    .log-table { width:100%; border-collapse:collapse; }
    .log-table th, .log-table td { padding:1rem; border-bottom:1px solid #e2e8f0; text-align:left; }
    .log-table th { text-transform:uppercase; font-size:0.75rem; color:#64748b; letter-spacing:0.06em; }
    .log-status { display:inline-flex; align-items:center; gap:0.35rem; padding:0.35rem 0.8rem; border-radius:999px; font-weight:700; font-size:0.75rem; }
    .log-status.in { background:#d1fae5; color:#15803d; }
    .log-status.out { background:#fef3c7; color:#92400e; }
    .empty-state { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:2rem; text-align:center; color:#64748b; }
</style>

<div class="grid-auto grid-3 dashboard-grid">
    <div class="dashboard-card">
        <h3>Total Attendance</h3>
        <div class="value"><?php echo htmlspecialchars($total_attendance); ?></div>
        <p class="note">All scanned records for your account.</p>
    </div>
    <div class="dashboard-card">
        <h3>Present Count</h3>
        <div class="value"><?php echo htmlspecialchars($present_count); ?></div>
        <p class="note">Total IN scans recorded.</p>
    </div>
    <div class="dashboard-card">
        <h3>Absent Count</h3>
        <div class="value"><?php echo htmlspecialchars($absent_count); ?></div>
        <p class="note">Sessions without an attendance log.</p>
    </div>
</div>

<div class="card-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
        <div>
            <h2 style="margin:0; font-size:1.125rem;">Latest Attendance</h2>
            <p style="margin:0.35rem 0 0; color:#64748b;">Most recent RFID attendance records.</p>
        </div>
        <a href="attendance_history.php" class="btn-outline">View full history</a>
    </div>

    <?php if (empty($recent_logs)): ?>
        <div class="empty-state">
            <div style="font-size:2rem; margin-bottom:1rem;">No attendance logs yet.</div>
            <p>Attend a session with your RFID card to see updates here.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="table log-table">
                <thead>
                    <tr>
                        <th>Session</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Method</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['session_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($log['scan_time'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($log['scan_time'])); ?></td>
                            <td><span class="log-status <?php echo strtolower($log['attendance_type']); ?>"><?php echo htmlspecialchars($log['attendance_type']); ?></span></td>
                            <td><?php echo htmlspecialchars($log['scan_method']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php render_student_footer(); ?>
