<?php
ob_start();
require_once __DIR__ . '/../layout.php';
$page_title = 'Attendance Reports';
render_header($page_title);

$deptFilter        = get_department_filter('asess', true);
$sessionDeptFilter = get_department_filter('', true);
$sessionsSql = "SELECT id, session_name, attendance_type FROM attendance_sessions";
if ($sessionDeptFilter[0] !== '') $sessionsSql .= " WHERE {$sessionDeptFilter[0]}";
$sessionsSql .= " ORDER BY created_at DESC";
$sessionsStmt = $pdo->prepare($sessionsSql);
$sessionsStmt->execute($sessionDeptFilter[1]);
$sessions = $sessionsStmt->fetchAll();

$filter_session = intval($_GET['session_id'] ?? 0);
$date_from      = $_GET['date_from'] ?? date('Y-m-d');
$date_to        = $_GET['date_to']   ?? date('Y-m-d');
$filter_type    = $_GET['type']      ?? '';
$reportData     = [];
$generated      = false;

if (isset($_GET['generate'])) {
    $generated = true;
    $where  = ['DATE(al.scan_time) BETWEEN ? AND ?'];
    $params = [$date_from, $date_to];
    if ($deptFilter[0] !== '') { $where[] = $deptFilter[0]; $params = array_merge($params, $deptFilter[1]); }
    if ($filter_session > 0)   { $where[] = 'al.session_id = ?'; $params[] = $filter_session; }
    if (in_array($filter_type, ['IN', 'OUT'])) { $where[] = 'al.attendance_type = ?'; $params[] = $filter_type; }
    $whereSQL = implode(' AND ', $where);
    $stmt = $pdo->prepare("SELECT al.id, al.rfid_uid, al.attendance_type, al.scan_method, al.scan_time,
        s.student_id AS student_code, s.first_name, s.last_name, s.course, s.year_level, s.section,
        asess.session_name
        FROM attendance_logs al
        JOIN students s ON al.student_id = s.id
        JOIN attendance_sessions asess ON al.session_id = asess.id
        WHERE $whereSQL ORDER BY al.scan_time DESC");
    $stmt->execute($params);
    $reportData = $stmt->fetchAll();
}

$totalIn        = $generated ? count(array_filter($reportData, fn($r) => $r['attendance_type'] === 'IN'))  : 0;
$totalOut       = $generated ? count(array_filter($reportData, fn($r) => $r['attendance_type'] === 'OUT')) : 0;
$uniqueStudents = $generated ? count(array_unique(array_column($reportData, 'student_code'))) : 0;
?>

<style>
    .page-wrap { padding: 1.5rem; font-family: 'Sora', sans-serif; background: #f8fafc; min-height: 100%; }

    /* Header */
    .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .page-header h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
    .page-header p  { font-size: 0.8125rem; color: #94a3b8; margin: 0.25rem 0 0; }

    .btn-back { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #475569; background: #fff; border: 1px solid #e2e8f0; text-decoration: none; transition: border-color 0.15s, color 0.15s; }
    .btn-back:hover { border-color: #94a3b8; color: #0f172a; }
    .btn-back svg { width: 14px; height: 14px; stroke: currentColor; }

    /* Filter card */
    .filter-card { background: #fff; border-radius: 1.25rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 1.375rem 1.5rem; margin-bottom: 1.25rem; }
    .filter-card-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9375rem; font-weight: 700; color: #0f172a; margin: 0 0 1.125rem; display: flex; align-items: center; gap: 0.625rem; }
    .filter-card-title svg { width: 16px; height: 16px; stroke: #fb8500; }

    .filter-grid { display: grid; grid-template-columns: 1.5fr 0.8fr 1fr 1fr auto; gap: 0.875rem; align-items: end; }
    @media (max-width: 900px) { .filter-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 480px) { .filter-grid { grid-template-columns: 1fr; } }

    .filter-group { display: flex; flex-direction: column; gap: 0.375rem; }
    .filter-group label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }

    input[type="date"], input[type="text"], select {
        width: 100%; padding: 0.625rem 0.875rem;
        border: 1px solid #e2e8f0; border-radius: 0.75rem;
        font-family: 'Sora', sans-serif; font-size: 0.8125rem;
        color: #0f172a; background: #f8fafc; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        -webkit-appearance: none;
    }
    input:focus, select:focus { border-color: #fb8500; background: #fff; box-shadow: 0 0 0 3px rgba(251,133,0,0.1); }
    select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 13px; padding-right: 2.25rem; cursor: pointer; }

    .filter-actions { display: flex; gap: 0.5rem; flex-wrap: wrap; }

    .btn { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.625rem 1.125rem; border-radius: 0.75rem; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.8125rem; font-weight: 700; border: 1px solid; cursor: pointer; transition: all 0.15s; text-decoration: none; white-space: nowrap; }
    .btn svg { width: 14px; height: 14px; stroke: currentColor; }
    .btn-primary  { background: #fb8500; color: #fff; border-color: #fb8500; }
    .btn-primary:hover  { background: #e07600; border-color: #e07600; box-shadow: 0 4px 10px rgba(251,133,0,0.25); }
    .btn-green    { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .btn-green:hover    { background: #16a34a; color: #fff; border-color: #16a34a; }
    .btn-slate    { background: #f8fafc; color: #475569; border-color: #e2e8f0; }
    .btn-slate:hover    { background: #e2e8f0; }

    /* Stat cards */
    .stat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 1.25rem; }
    @media (max-width: 640px) { .stat-grid { grid-template-columns: 1fr 1fr; } }

    .stat-card { background: #fff; border-radius: 1.25rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 1.25rem; display: flex; flex-direction: column; gap: 0.5rem; position: relative; overflow: hidden; }
    .stat-card::before { content: ''; position: absolute; top: -1.5rem; right: -1.5rem; width: 5rem; height: 5rem; border-radius: 50%; opacity: 0.35; }
    .stat-card.blue::before  { background: #caf0f8; }
    .stat-card.green::before { background: #d1fae5; }
    .stat-card.amber::before { background: #fef3c7; }
    .stat-card.purple::before{ background: #ede9fe; }

    .stat-label { font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; }
    .stat-number { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 2rem; font-weight: 800; line-height: 1; }
    .stat-card.blue   .stat-number { color: #0369a1; }
    .stat-card.green  .stat-number { color: #16a34a; }
    .stat-card.amber  .stat-number { color: #b45309; }
    .stat-card.purple .stat-number { color: #7c3aed; }
    .stat-sub { font-size: 0.75rem; color: #94a3b8; }

    /* Alert */
    .alert { border-radius: 0.875rem; padding: 0.875rem 1rem; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 0.75rem; }
    .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; }
    .alert svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; stroke: #1d4ed8; }
    .alert p { font-size: 0.8125rem; font-weight: 500; color: #1d4ed8; margin: 0; }

    /* Table card */
    .table-card { background: #fff; border-radius: 1.25rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
    .table-card-header { padding: 1.125rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 0.75rem; }
    .table-card-header h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9375rem; font-weight: 700; color: #0f172a; margin: 0; }
    .record-count { font-size: 0.75rem; font-weight: 600; color: #94a3b8; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.25rem 0.625rem; border-radius: 2rem; }

    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
    thead tr { background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
    thead th { padding: 0.75rem 1.125rem; text-align: left; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.1s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #fafafa; }
    tbody td { padding: 0.75rem 1.125rem; color: #334155; vertical-align: middle; }

    .row-num { font-size: 0.75rem; color: #cbd5e1; font-weight: 600; }
    .student-cell { display: flex; align-items: center; gap: 0.625rem; }
    .avatar { width: 1.875rem; height: 1.875rem; border-radius: 50%; background: #caf0f8; display: flex; align-items: center; justify-content: center; font-size: 0.6875rem; font-weight: 700; color: #0369a1; flex-shrink: 0; }
    .student-name { font-weight: 600; color: #0f172a; }
    .student-id-code { font-size: 0.75rem; color: #94a3b8; font-family: 'Courier New', monospace; }
    .course-cell .course { font-weight: 500; }
    .course-cell .yr-sec { font-size: 0.75rem; color: #94a3b8; }

    .badge { display: inline-flex; align-items: center; gap: 0.3rem; padding: 0.25rem 0.625rem; border-radius: 2rem; font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.04em; border: 1px solid; }
    .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
    .badge.in     { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .badge.out    { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .badge.method { background: #f8fafc; color: #475569; border-color: #e2e8f0; }

    .scan-date { font-weight: 500; }
    .scan-time { font-size: 0.75rem; color: #94a3b8; }

    /* Empty state */
    .empty-state { padding: 4rem 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.75rem; }
    .empty-icon { width: 3rem; height: 3rem; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; }
    .empty-icon svg { width: 1.375rem; height: 1.375rem; stroke: #94a3b8; }
    .empty-state p { font-size: 0.875rem; color: #94a3b8; font-weight: 500; margin: 0; }

    /* Prompt state (not yet generated) */
    .prompt-state { background: #fff; border-radius: 1.25rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); padding: 4rem 1.5rem; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 0.875rem; }
    .prompt-icon { width: 3.5rem; height: 3.5rem; border-radius: 1rem; background: #fff3e0; display: flex; align-items: center; justify-content: center; }
    .prompt-icon svg { width: 1.75rem; height: 1.75rem; stroke: #fb8500; }
    .prompt-state h3 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1rem; font-weight: 700; color: #0f172a; margin: 0; }
    .prompt-state p { font-size: 0.8125rem; color: #94a3b8; margin: 0; max-width: 280px; }

    @media print {
        .filter-card, .page-header .btn-back, .filter-actions { display: none !important; }
        .page-wrap { padding: 0; background: #fff; }
        .table-card { box-shadow: none; border: 1px solid #ccc; }
    }
</style>

<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1>Attendance Reports</h1>
            <p>Generate and export attendance data by date range</p>
        </div>
        <a href="attendance_logs.php" class="btn-back">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Logs
        </a>
    </div>

    <div class="filter-card">
        <p class="filter-card-title">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            Generate Report
        </p>
        <form method="GET">
            <input type="hidden" name="generate" value="1">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Session</label>
                    <select name="session_id">
                        <option value="0">All Sessions</option>
                        <?php foreach ($sessions as $sess): ?>
                            <option value="<?= $sess['id'] ?>" <?= $filter_session == $sess['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sess['session_name']) ?> (<?= $sess['attendance_type'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Type</label>
                    <select name="type">
                        <option value="">All</option>
                        <option value="IN"  <?= $filter_type === 'IN'  ? 'selected' : '' ?>>IN</option>
                        <option value="OUT" <?= $filter_type === 'OUT' ? 'selected' : '' ?>>OUT</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" required>
                </div>
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" required>
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        Generate
                    </button>
                    <?php if ($generated && !empty($reportData)): ?>
                        <button type="button" class="btn btn-green" onclick="exportCSV()">
                            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            CSV
                        </button>
                        <button type="button" class="btn btn-slate" onclick="window.print()">
                            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if ($generated): ?>

        <div class="stat-grid">
            <div class="stat-card blue">
                <span class="stat-label">Total Scans</span>
                <span class="stat-number"><?= count($reportData) ?></span>
                <span class="stat-sub">All records</span>
            </div>
            <div class="stat-card green">
                <span class="stat-label">Time-IN</span>
                <span class="stat-number"><?= $totalIn ?></span>
                <span class="stat-sub">Entry scans</span>
            </div>
            <div class="stat-card amber">
                <span class="stat-label">Time-OUT</span>
                <span class="stat-number"><?= $totalOut ?></span>
                <span class="stat-sub">Exit scans</span>
            </div>
            <div class="stat-card purple">
                <span class="stat-label">Unique Students</span>
                <span class="stat-number"><?= $uniqueStudents ?></span>
                <span class="stat-sub">Individuals</span>
            </div>
        </div>

        <?php if (empty($reportData)): ?>
            <div class="alert alert-info">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p>No attendance records found for the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="table-card-header">
                    <h2>
                        <?= date('M d, Y', strtotime($date_from)) ?>
                        <?= $date_from !== $date_to ? ' — ' . date('M d, Y', strtotime($date_to)) : '' ?>
                    </h2>
                    <span class="record-count"><?= count($reportData) ?> record<?= count($reportData) !== 1 ? 's' : '' ?></span>
                </div>
                <div class="table-wrap">
                    <table id="report-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Course / Year & Sec</th>
                                <th>Session</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Date</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reportData as $i => $row):
                                $dt = new DateTime($row['scan_time']);
                            ?>
                            <tr>
                                <td><span class="row-num"><?= $i + 1 ?></span></td>
                                <td>
                                    <div class="student-cell">
                                        <div class="avatar"><?= strtoupper(substr($row['first_name'], 0, 1)) ?></div>
                                        <div>
                                            <div class="student-name"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            <div class="student-id-code"><?= htmlspecialchars($row['student_code']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="course-cell">
                                    <div class="course"><?= htmlspecialchars($row['course']) ?></div>
                                    <div class="yr-sec"><?= htmlspecialchars($row['year_level'] . ' — ' . $row['section']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['session_name']) ?></td>
                                <td>
                                    <span class="badge <?= $row['attendance_type'] === 'IN' ? 'in' : 'out' ?>">
                                        <span class="badge-dot"></span>
                                        <?= htmlspecialchars($row['attendance_type']) ?>
                                    </span>
                                </td>
                                <td><span class="badge method"><?= htmlspecialchars($row['scan_method']) ?></span></td>
                                <td><span class="scan-date"><?= $dt->format('M d, Y') ?></span></td>
                                <td><span class="scan-time"><?= $dt->format('h:i:s A') ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="prompt-state">
            <div class="prompt-icon">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3>No report generated yet</h3>
            <p>Select your filters above and click Generate to view attendance data.</p>
        </div>
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
    a.download = 'attendance_report_<?= $date_from ?>_<?= $date_to ?>.csv';
    a.click();
}
</script>

<?php render_footer(); ?>