<?php
$page_title = 'Attendance History';
require_once __DIR__ . '/layout.php';
render_student_header($page_title);

$student   = get_logged_student();
$studentId = $student['id'];
$search    = trim($_GET['search'] ?? '');
$page      = max(1, intval($_GET['page'] ?? 1));
$perPage   = 12;

$where  = ['al.student_id = ?'];
$params = [$studentId];

if ($search !== '') {
    $where[] = '(asess.session_name LIKE ? OR al.attendance_type LIKE ? OR al.rfid_uid LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

$totalStmt = $pdo->prepare(
    "SELECT COUNT(*) FROM (
        SELECT DATE(al.scan_time), al.session_id
        FROM attendance_logs al
        JOIN attendance_sessions asess ON al.session_id = asess.id
        WHERE $whereSql
        GROUP BY DATE(al.scan_time), al.session_id
    ) AS sub"
);
$totalStmt->execute($params);
$totalRows  = (int)$totalStmt->fetchColumn();
$totalPages = max(1, ceil($totalRows / $perPage));
$offset     = ($page - 1) * $perPage;

$dataStmt = $pdo->prepare(
    "SELECT
        DATE(al.scan_time) AS attendance_date,
        MIN(CASE WHEN al.attendance_type = 'IN'  THEN al.scan_time END) AS time_in,
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

$paramIndex = 1;
foreach ($params as $param) {
    $dataStmt->bindValue($paramIndex++, $param, PDO::PARAM_STR);
}
$dataStmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
$dataStmt->bindValue($paramIndex++, $offset,  PDO::PARAM_INT);

$dataStmt->execute();
$attendanceRows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>

    /* ── TOP STATS ── */
    .stats-row {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    @media (max-width: 640px) {
        .stats-row { grid-template-columns: 1fr; }
    }

    .stat-card {
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 1.25rem;
        padding: 1.25rem 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.875rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .stat-icon.orange { background: #fff7ed; color: #fb8500; }
    .stat-icon.green  { background: #f0fdf4; color: #16a34a; }
    .stat-icon.red    { background: #fef2f2; color: #dc2626; }

    .stat-label {
        font-size: 0.72rem;
        font-weight: 600;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        margin-bottom: 0.2rem;
    }

    .stat-value {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.5rem;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }

    /* ── MAIN CARD ── */
    .history-card {
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .history-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .history-card-header h2 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .history-card-header p {
        font-size: 0.78rem;
        color: #94a3b8;
        margin: 0.15rem 0 0;
    }

    /* Search */
    .search-form {
        display: flex;
        gap: 0.65rem;
        align-items: center;
    }

    .search-input {
        height: 2.6rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 0 1rem;
        font-size: 0.8125rem;
        color: #0f172a;
        background: #fff;
        outline: none;
        transition: 0.15s;
        font-family: 'Sora', sans-serif;
        width: 220px;
    }

    .search-input:focus {
        border-color: #fb8500;
        box-shadow: 0 0 0 3px rgba(251,133,0,0.08);
    }

    .search-btn {
        height: 2.6rem;
        border: none;
        background: #fb8500;
        color: #fff;
        border-radius: 0.875rem;
        padding: 0 1.1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.15s;
        font-family: 'Sora', sans-serif;
        white-space: nowrap;
    }

    .search-btn:hover { background: #ea7b00; }

    .clear-btn {
        height: 2.6rem;
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        color: #64748b;
        border-radius: 0.875rem;
        padding: 0 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.15s;
        font-family: 'Sora', sans-serif;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
    }

    .clear-btn:hover { background: #e2e8f0; color: #0f172a; }

    /* Table */
    .table-wrap { overflow-x: auto; }

    .history-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 640px;
    }

    .history-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    .history-table thead th {
        padding: 0.85rem 1.5rem;
        text-align: left;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
        white-space: nowrap;
    }

    .history-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.12s;
    }

    .history-table tbody tr:last-child {
        border-bottom: none;
    }

    .history-table tbody tr:hover {
        background: #f8fafc;
    }

    .history-table tbody td {
        padding: 1rem 1.5rem;
        font-size: 0.8125rem;
        color: #334155;
        vertical-align: middle;
        white-space: nowrap;
    }

    .date-cell {
        font-weight: 600;
        color: #0f172a;
    }

    .session-cell {
        color: #475569;
        max-width: 180px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .time-cell {
        font-family: 'Sora', monospace;
        font-size: 0.8125rem;
    }

    .time-in  { color: #16a34a; font-weight: 600; }
    .time-out { color: #dc2626; font-weight: 600; }
    .time-na  { color: #cbd5e1; }

    .badge-status {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .badge-present { background: #dcfce7; color: #15803d; }
    .badge-absent  { background: #fee2e2; color: #b91c1c; }

    .remarks-cell {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    .remarks-complete { color: #16a34a; }

    /* Empty state */
    .empty-state {
        padding: 3.5rem 1.5rem;
        text-align: center;
    }

    .empty-icon {
        width: 4rem;
        height: 4rem;
        border-radius: 1rem;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.75rem;
        color: #94a3b8;
        margin: 0 auto 1rem;
    }

    .empty-state h3 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 0.4rem;
    }

    .empty-state p {
        font-size: 0.8125rem;
        color: #94a3b8;
        margin: 0;
    }

    /* Pagination */
    .pagination-wrap {
        padding: 1.25rem 1.5rem;
        border-top: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    .pagination-info {
        font-size: 0.78rem;
        color: #94a3b8;
    }

    .pagination-btns {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .page-btn {
        min-width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.65rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #475569;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.15s;
        padding: 0 0.5rem;
    }

    .page-btn:hover {
        border-color: #fb8500;
        color: #fb8500;
    }

    .page-btn.active {
        background: #fb8500;
        border-color: #fb8500;
        color: #fff;
    }

</style>

<?php
// Compute quick stats from all records (not just this page)
$statsStmt = $pdo->prepare(
    "SELECT
        COUNT(DISTINCT DATE(al.scan_time), al.session_id) AS total_sessions,
        SUM(CASE WHEN MIN(CASE WHEN al.attendance_type='IN' THEN al.scan_time END) IS NOT NULL THEN 1 ELSE 0 END) AS present_count
    FROM attendance_logs al
    JOIN attendance_sessions asess ON al.session_id = asess.id
    WHERE al.student_id = ?
    GROUP BY al.student_id"
);
// Use subquery approach for stats too
$statsStmt = $pdo->prepare(
    "SELECT
        COUNT(*) AS total_sessions,
        SUM(CASE WHEN time_in IS NOT NULL THEN 1 ELSE 0 END) AS present_count
    FROM (
        SELECT
            DATE(al.scan_time) AS d,
            al.session_id,
            MIN(CASE WHEN al.attendance_type='IN' THEN al.scan_time END) AS time_in
        FROM attendance_logs al
        JOIN attendance_sessions asess ON al.session_id = asess.id
        WHERE al.student_id = ?
        GROUP BY DATE(al.scan_time), al.session_id
    ) AS sub"
);
$statsStmt->execute([$studentId]);
$stats        = $statsStmt->fetch(PDO::FETCH_ASSOC);
$totalSess    = (int)($stats['total_sessions'] ?? 0);
$presentCount = (int)($stats['present_count']  ?? 0);
$absentCount  = $totalSess - $presentCount;
?>

<!-- Stats row -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-icon orange"><i class="bi bi-calendar-check"></i></div>
        <div>
            <div class="stat-label">Total Sessions</div>
            <div class="stat-value"><?= $totalSess ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green"><i class="bi bi-check-circle"></i></div>
        <div>
            <div class="stat-label">Present</div>
            <div class="stat-value"><?= $presentCount ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red"><i class="bi bi-x-circle"></i></div>
        <div>
            <div class="stat-label">Absent</div>
            <div class="stat-value"><?= $absentCount ?></div>
        </div>
    </div>
</div>

<!-- Main table card -->
<div class="history-card">

    <div class="history-card-header">
        <div>
            <h2>Attendance Records</h2>
            <p>
                <?= $totalRows ?> record<?= $totalRows !== 1 ? 's' : '' ?> found
                <?= $search !== '' ? ' for "' . htmlspecialchars($search) . '"' : '' ?>
            </p>
        </div>

        <form method="GET" class="search-form">
            <input
                type="text"
                name="search"
                class="search-input"
                placeholder="Search session or status..."
                value="<?= htmlspecialchars($search) ?>"
            >
            <button type="submit" class="search-btn">Search</button>
            <?php if ($search !== ''): ?>
                <a href="attendance_history.php" class="clear-btn">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <?php if (empty($attendanceRows)): ?>

        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-calendar-x"></i></div>
            <h3>No attendance records found</h3>
            <p>
                <?= $search !== '' ? 'Try a different search term.' : 'Attend a session with your RFID card to populate this list.' ?>
            </p>
        </div>

    <?php else: ?>

        <div class="table-wrap">
            <table class="history-table">
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
                            $isPresent   = !empty($row['time_in']);
                            $statusLabel = $isPresent ? 'Present' : 'Absent';
                            $badgeClass  = $isPresent ? 'badge-present' : 'badge-absent';
                            $hasOut      = !empty($row['time_out']);
                        ?>
                        <tr>
                            <td class="date-cell">
                                <?= date('M d, Y', strtotime($row['attendance_date'])) ?>
                            </td>
                            <td class="session-cell">
                                <?= htmlspecialchars($row['session_name']) ?>
                            </td>
                            <td class="time-cell">
                                <?php if ($row['time_in']): ?>
                                    <span class="time-in">
                                        <i class="bi bi-box-arrow-in-right"></i>
                                        <?= date('h:i A', strtotime($row['time_in'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="time-na">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="time-cell">
                                <?php if ($hasOut): ?>
                                    <span class="time-out">
                                        <i class="bi bi-box-arrow-right"></i>
                                        <?= date('h:i A', strtotime($row['time_out'])) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="time-na">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $badgeClass ?>">
                                    <?= htmlspecialchars($statusLabel) ?>
                                </span>
                            </td>
                            <td class="remarks-cell <?= $hasOut ? 'remarks-complete' : '' ?>">
                                <?= $hasOut ? '✓ Complete' : 'No OUT scan' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination-wrap">
                <div class="pagination-info">
                    Page <?= $page ?> of <?= $totalPages ?>
                </div>
                <div class="pagination-btns">
                    <?php if ($page > 1): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="page-btn">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a
                            href="?search=<?= urlencode($search) ?>&page=<?= $i ?>"
                            class="page-btn <?= $i === $page ? 'active' : '' ?>"
                        >
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="page-btn">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php render_student_footer(); ?>