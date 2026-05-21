<?php
$page_title = 'Attendance History';
require_once __DIR__ . '/layout.php';
render_student_header($page_title);

$student = get_logged_student();
$studentId = $student['id'];
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$where = ['al.student_id = ?'];
$params = [$studentId];

if ($search !== '') {
    $where[] = '(asess.session_name LIKE ? OR al.attendance_type LIKE ? OR al.rfid_uid LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);
$totalStmt = $pdo->prepare("SELECT COUNT(DISTINCT DATE(al.scan_time), al.session_id) FROM attendance_logs al JOIN attendance_sessions asess ON al.session_id = asess.id WHERE $whereSql");
$totalStmt->execute($params);
$totalRows = $totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset = ($page - 1) * $perPage;

$dataStmt = $pdo->prepare(
    "SELECT DATE(al.scan_time) AS attendance_date,
        MIN(CASE WHEN al.attendance_type = 'IN' THEN al.scan_time END) AS time_in,
        MAX(CASE WHEN al.attendance_type = 'OUT' THEN al.scan_time END) AS time_out,
        MAX(al.attendance_type) AS status,
        asess.session_name
    FROM attendance_logs al
    JOIN attendance_sessions asess ON al.session_id = asess.id
    WHERE $whereSql
    GROUP BY DATE(al.scan_time), al.session_id
    ORDER BY attendance_date DESC
    LIMIT ? OFFSET ?"
);
$params[] = $perPage;
$params[] = $offset;
$dataStmt->execute($params);
$attendanceRows = $dataStmt->fetchAll();
?>

<style>
    .toolbar { display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between; margin-bottom:1.5rem; }
    .toolbar form { flex:1; }
    .toolbar input { width:100%; }
    .history-card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.5rem; box-shadow:0 18px 40px rgba(15,23,42,0.04); }
    .history-table { width:100%; border-collapse:collapse; }
    .history-table th, .history-table td { padding:1rem; border-bottom:1px solid #e2e8f0; }
    .history-table th { text-transform:uppercase; font-size:0.75rem; letter-spacing:0.06em; color:#64748b; }
    .history-table tbody tr:hover { background:#f8fafc; }
    .badge-status { display:inline-flex; align-items:center; gap:0.35rem; padding:0.35rem 0.85rem; border-radius:999px; font-size:0.75rem; font-weight:700; }
    .badge-present { background:#d1fae5; color:#15803d; }
    .badge-absent { background:#fee2e2; color:#b91c1c; }
    .empty-state { padding:2rem; text-align:center; color:#64748b; }
    .pagination { display:flex; flex-wrap:wrap; gap:0.75rem; justify-content:flex-end; margin-top:1rem; }
    .pagination a { display:inline-flex; align-items:center; justify-content:center; padding:0.75rem 1rem; border-radius:0.75rem; border:1px solid #e2e8f0; background:#fff; color:#0f172a; text-decoration:none; }
    .pagination a.active { background:#fb8500; color:#fff; border-color:#fb8500; }
</style>

<div class="history-card">
    <div class="toolbar">
        <div>
            <h2 style="margin:0;">Attendance History</h2>
            <p style="margin:0.5rem 0 0; color:#64748b;">View your personal attendance records and remarks.</p>
        </div>
        <form method="GET" style="max-width:360px; width:100%;">
            <div style="display:flex; gap:0.75rem; align-items:center;">
                <input type="text" name="search" class="input-field" placeholder="Search session, status, RFID" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-primary">Search</button>
            </div>
        </form>
    </div>

    <?php if (empty($attendanceRows)): ?>
        <div class="empty-state">
            <div style="font-size:1.25rem; margin-bottom:0.75rem;">No attendance history available.</div>
            <p>Attend a session with your student RFID card to populate this list.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="history-table table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Session</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendanceRows as $row): ?>
                        <?php
                            $statusLabel = $row['time_in'] ? 'Present' : 'Absent';
                            $badgeClass = $row['time_in'] ? 'badge-present' : 'badge-absent';
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($row['attendance_date'])); ?></td>
                            <td><?php echo htmlspecialchars($row['session_name']); ?></td>
                            <td><?php echo $row['time_in'] ? date('h:i A', strtotime($row['time_in'])) : '-'; ?></td>
                            <td><?php echo $row['time_out'] ? date('h:i A', strtotime($row['time_out'])) : '-'; ?></td>
                            <td><span class="badge-status <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
                            <td><?php echo $row['time_out'] ? 'Attendance complete' : 'No OUT scan recorded'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>" class="<?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php render_student_footer(); ?>
