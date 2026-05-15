<?php
require_once __DIR__ . '/../layout.php';
$page_title = 'Attendance Logs';
render_header($page_title);

$filter_session = intval($_GET['session_id'] ?? 0);
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_type = $_GET['type'] ?? '';
$filter_search = trim($_GET['search'] ?? '');

$where = ['DATE(al.scan_time) = ?'];
$params = [$filter_date];

if ($filter_session > 0) {
    $where[] = 'al.session_id = ?';
    $params[] = $filter_session;
}
if (in_array($filter_type, ['IN', 'OUT'])) {
    $where[] = 'al.attendance_type = ?';
    $params[] = $filter_type;
}
if ($filter_search !== '') {
    $where[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR al.rfid_uid LIKE ? )";
    $like = '%' . $filter_search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT al.id, al.rfid_uid, al.attendance_type, al.scan_method, al.scan_time, s.student_id AS student_code, s.first_name, s.last_name, s.course, s.year_level, s.section, asess.session_name FROM attendance_logs al JOIN students s ON al.student_id = s.id JOIN attendance_sessions asess ON al.session_id = asess.id WHERE $whereSQL ORDER BY al.scan_time DESC");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$sessions = $pdo->query("SELECT id, session_name FROM attendance_sessions ORDER BY created_at DESC")->fetchAll();
?>
<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0"><i class="bi bi-journal-text me-2 text-primary"></i>Attendance Logs</h2>
        <div class="d-flex align-items-center gap-2">
            <span id="live-dot" class="badge bg-success d-flex align-items-center gap-1"><span class="spinner-grow spinner-grow-sm" role="status"></span> LIVE</span>
            <a href="attendance_reports.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-bar-graph me-1"></i>Reports</a>
        </div>
    </div>

    <form method="GET" class="card card-body mb-3 p-3 shadow-sm">
        <div class="row g-2 align-items-end">
            <div class="col-sm-6 col-md-3">
                <label class="form-label small mb-1">Date</label>
                <input type="date" class="form-control form-control-sm" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div class="col-sm-6 col-md-3">
                <label class="form-label small mb-1">Session</label>
                <select class="form-select form-select-sm" name="session_id">
                    <option value="0">All Sessions</option>
                    <?php foreach ($sessions as $sess): ?>
                        <option value="<?php echo $sess['id']; ?>" <?php echo $filter_session == $sess['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($sess['session_name']); ?></option>
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
            <div class="col-sm-6 col-md-3">
                <label class="form-label small mb-1">Search</label>
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Name, Student ID, RFID…" value="<?php echo htmlspecialchars($filter_search); ?>">
            </div>
            <div class="col-md-1 d-flex gap-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-search"></i></button>
                <a href="attendance_logs.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x-lg"></i></a>
            </div>
        </div>
    </form>

    <div class="d-flex gap-2 mb-3 flex-wrap">
        <?php
        $totalIn = count(array_filter($logs, fn($l) => $l['attendance_type'] === 'IN'));
        $totalOut = count(array_filter($logs, fn($l) => $l['attendance_type'] === 'OUT'));
        ?>
        <span class="badge bg-primary fs-6 px-3 py-2">Total: <?php echo count($logs); ?></span>
        <span class="badge bg-success fs-6 px-3 py-2"><i class="bi bi-box-arrow-in-right me-1"></i>IN: <?php echo $totalIn; ?></span>
        <span class="badge bg-warning text-dark fs-6 px-3 py-2"><i class="bi bi-box-arrow-right me-1"></i>OUT: <?php echo $totalOut; ?></span>
        <span class="badge bg-light text-dark fs-6 px-3 py-2 border"><i class="bi bi-calendar3 me-1"></i><?php echo date('F d, Y', strtotime($filter_date)); ?></span>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($logs)): ?>
                <div class="text-center py-5 text-muted"><i class="bi bi-inbox fs-1"></i><p class="mt-2">No attendance logs for the selected filters.</p></div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="logs-table">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Student</th>
                                <th>Student ID</th>
                                <th>Course / Yr & Sec</th>
                                <th>RFID UID</th>
                                <th>Session</th>
                                <th>Type</th>
                                <th>Method</th>
                                <th>Scan Time</th>
                            </tr>
                        </thead>
                        <tbody id="logs-tbody">
                            <?php foreach ($logs as $i => $log): ?>
                                <tr data-log-id="<?php echo $log['id']; ?>">
                                    <td class="text-muted small"><?php echo $i + 1; ?></td>
                                    <td class="fw-semibold"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></td>
                                    <td class="text-muted small"><?php echo htmlspecialchars($log['student_code']); ?></td>
                                    <td class="small"><?php echo htmlspecialchars($log['course']); ?><br><span class="text-muted"><?php echo htmlspecialchars($log['year_level'] . ' — ' . $log['section']); ?></span></td>
                                    <td><code class="small"><?php echo htmlspecialchars($log['rfid_uid']); ?></code></td>
                                    <td class="small"><?php echo htmlspecialchars($log['session_name']); ?></td>
                                    <td><span class="badge <?php echo $log['attendance_type'] === 'IN' ? 'bg-primary' : 'bg-warning text-dark'; ?>"><?php echo htmlspecialchars($log['attendance_type']); ?></span></td>
                                    <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($log['scan_method']); ?></span></td>
                                    <td class="small text-nowrap"><?php $dt = new DateTime($log['scan_time']); echo $dt->format('M d, Y') . '<br><span class="text-muted">' . $dt->format('h:i:s A') . '</span>'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    let knownIds = new Set([...document.querySelectorAll('#logs-tbody tr[data-log-id]')].map(r => r.dataset.logId));
    const pollDate = '<?php echo $filter_date; ?>';
    const pollSession = '<?php echo $filter_session; ?>';
    const pollType = '<?php echo $filter_type; ?>';

    async function poll() {
        try {
            const qs = new URLSearchParams({ date: pollDate, session_id: pollSession, type: pollType, format: 'json' });
            const res = await fetch('attendance_logs_ajax.php?' + qs);
            if (!res.ok) return;
            const data = await res.json();
            let newCount = 0;
            const tbody = document.getElementById('logs-tbody');
            data.forEach(log => {
                if (knownIds.has(String(log.id))) return;
                knownIds.add(String(log.id));
                newCount++;
                const tr = document.createElement('tr');
                tr.dataset.logId = log.id;
                tr.style.animation = 'highlightNew 2s ease-out';
                tr.innerHTML = `
                    <td class="text-muted small">—</td>
                    <td class="fw-semibold">${esc(log.full_name)}</td>
                    <td class="text-muted small">${esc(log.student_code)}</td>
                    <td class="small">${esc(log.course)}<br><span class="text-muted">${esc(log.year_level + ' — ' + log.section)}</span></td>
                    <td><code class="small">${esc(log.rfid_uid)}</code></td>
                    <td class="small">${esc(log.session_name)}</td>
                    <td><span class="badge ${log.attendance_type === 'IN' ? 'bg-primary' : 'bg-warning text-dark'}">${esc(log.attendance_type)}</span></td>
                    <td><span class="badge bg-light text-dark border">${esc(log.scan_method)}</span></td>
                    <td class="small text-nowrap">${esc(log.scan_date)}<br><span class="text-muted">${esc(log.scan_time_fmt)}</span></td>
                `;
                tbody.prepend(tr);
            });
            if (newCount > 0) {
                const dot = document.getElementById('live-dot');
                dot.classList.replace('bg-success','bg-danger');
                setTimeout(() => dot.classList.replace('bg-danger','bg-success'), 800);
            }
        } catch (e) {
        }
    }
    setInterval(poll, 5000);
    function esc(s) { return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
})();
</script>

<style>
@keyframes highlightNew { 0% { background: #d1fae5; } 100% { background: transparent; } }
</style>

<?php render_footer(); ?>