<?php
ob_start();
require_once __DIR__ . '/../layout.php';
$page_title = 'Attendance Reports';
render_header($page_title);

$sessions = $pdo->query("SELECT id, session_name, attendance_type FROM attendance_sessions ORDER BY created_at DESC")->fetchAll();
$filter_session = intval($_GET['session_id'] ?? 0);
$date_from = $_GET['date_from'] ?? date('Y-m-d');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$filter_type = $_GET['type'] ?? '';
$reportData = [];
$generated = false;

if (isset($_GET['generate'])) {
    $generated = true;
    $where = ['DATE(al.scan_time) BETWEEN ? AND ?'];
    $params = [$date_from, $date_to];

    if ($filter_session > 0) {
        $where[] = 'al.session_id = ?';
        $params[] = $filter_session;
    }
    if (in_array($filter_type, ['IN', 'OUT'])) {
        $where[] = 'al.attendance_type = ?';
        $params[] = $filter_type;
    }

    $whereSQL = implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT al.id, al.rfid_uid, al.attendance_type, al.scan_method, al.scan_time, s.student_id AS student_code, s.first_name, s.last_name, s.course, s.year_level, s.section, asess.session_name FROM attendance_logs al JOIN students s ON al.student_id = s.id JOIN attendance_sessions asess ON al.session_id = asess.id WHERE $whereSQL ORDER BY al.scan_time DESC");
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();
}
?>
<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0"><i class="bi bi-file-earmark-bar-graph me-2 text-primary"></i>Attendance Reports</h2>
        <a href="attendance_logs.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back to Logs</a>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header fw-semibold">Generate Report</div>
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="generate" value="1">
                <div class="col-sm-6 col-md-3">
                    <label class="form-label small mb-1">Session</label>
                    <select class="form-select form-select-sm" name="session_id">
                        <option value="0">All Sessions</option>
                        <?php foreach ($sessions as $sess): ?>
                            <option value="<?php echo $sess['id']; ?>" <?php echo $filter_session == $sess['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sess['session_name']); ?> (<?php echo $sess['attendance_type']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select class="form-select form-select-sm" name="type">
                        <option value="">All</option>
                        <option value="IN" <?php echo $filter_type === 'IN' ? 'selected' : ''; ?>>IN</option>
                        <option value="OUT" <?php echo $filter_type === 'OUT' ? 'selected' : ''; ?>>OUT</option>
                    </select>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small mb-1">Date From</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" required>
                </div>
                <div class="col-sm-6 col-md-2">
                    <label class="form-label small mb-1">Date To</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" required>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-bar-chart me-1"></i> Generate</button>
                    <?php if ($generated && !empty($reportData)): ?>
                        <button type="button" class="btn btn-success btn-sm" onclick="exportCSV()"><i class="bi bi-download me-1"></i> Export CSV</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($generated): ?>
        <?php
        $totalIn = count(array_filter($reportData, fn($r) => $r['attendance_type'] === 'IN'));
        $totalOut = count(array_filter($reportData, fn($r) => $r['attendance_type'] === 'OUT'));
        $uniqueStudents = count(array_unique(array_column($reportData, 'student_code')));
        ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3"><div class="card text-center border-primary"><div class="card-body py-3"><div class="fs-2 fw-bold text-primary"><?php echo count($reportData); ?></div><div class="small text-muted">Total Scans</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card text-center border-success"><div class="card-body py-3"><div class="fs-2 fw-bold text-success"><?php echo $totalIn; ?></div><div class="small text-muted">Time-IN</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card text-center border-warning"><div class="card-body py-3"><div class="fs-2 fw-bold text-warning"><?php echo $totalOut; ?></div><div class="small text-muted">Time-OUT</div></div></div></div>
            <div class="col-6 col-md-3"><div class="card text-center border-info"><div class="card-body py-3"><div class="fs-2 fw-bold text-info"><?php echo $uniqueStudents; ?></div><div class="small text-muted">Unique Students</div></div></div></div>
        </div>
    <?php endif; ?>

    <?php if ($generated): ?>
        <?php if (empty($reportData)): ?>
            <div class="alert alert-info">No attendance records found for the selected filters.</div>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Results: <?php echo date('M d, Y', strtotime($date_from)); ?><?php echo $date_from !== $date_to ? ' — ' . date('M d, Y', strtotime($date_to)) : ''; ?></span>
                    <span class="text-muted small"><?php echo count($reportData); ?> record(s)</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="report-table">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Student Name</th>
                                    <th>Student ID</th>
                                    <th>Course / Yr & Sec</th>
                                    <th>Session</th>
                                    <th>Type</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($reportData as $i => $row): ?>
                                    <tr>
                                        <td class="text-muted small"><?php echo $i + 1; ?></td>
                                        <td class="fw-semibold"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($row['student_code']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($row['course']); ?><br><span class="text-muted"><?php echo htmlspecialchars($row['year_level'] . ' — ' . $row['section']); ?></span></td>
                                        <td class="small"><?php echo htmlspecialchars($row['session_name']); ?></td>
                                        <td><span class="badge <?php echo $row['attendance_type'] === 'IN' ? 'bg-primary' : 'bg-warning text-dark'; ?>"><?php echo htmlspecialchars($row['attendance_type']); ?></span></td>
                                        <td class="small"><?php echo htmlspecialchars($row['scan_method']); ?></td>
                                        <td class="small"><?php echo date('M d, Y', strtotime($row['scan_time'])); ?></td>
                                        <td class="small"><?php echo date('h:i:s A', strtotime($row['scan_time'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function exportCSV() {
    const table = document.getElementById('report-table');
    if (!table) return;
    let csv = [];
    for (const row of table.querySelectorAll('tr')) {
        const cells = [...row.querySelectorAll('th, td')].map(c => '"' + c.innerText.replace(/"/g, '""').replace(/\n/g, ' ') + '"');
        csv.push(cells.join(','));
    }
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'attendance_report_<?php echo $date_from; ?>_<?php echo $date_to; ?>.csv';
    a.click();
}
</script>

<?php render_footer(); ?>