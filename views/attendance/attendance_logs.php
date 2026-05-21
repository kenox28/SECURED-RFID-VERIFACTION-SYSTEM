<?php
require_once __DIR__ . '/../layout.php';
$page_title = 'Attendance Logs';
render_header($page_title);

$filter_session = intval($_GET['session_id'] ?? 0);
$filter_date    = $_GET['date'] ?? date('Y-m-d');
$filter_type    = $_GET['type'] ?? '';
$filter_search  = trim($_GET['search'] ?? '');

$deptFilter        = get_department_filter('asess', true);
$sessionDeptFilter = get_department_filter('', true);
$where  = ['DATE(al.scan_time) = ?'];
$params = [$filter_date];

if ($deptFilter[0] !== '') {
    $where[]  = $deptFilter[0];
    $params   = array_merge($params, $deptFilter[1]);
}
if ($filter_session > 0) { $where[] = 'al.session_id = ?'; $params[] = $filter_session; }
if (in_array($filter_type, ['IN', 'OUT'])) { $where[] = 'al.attendance_type = ?'; $params[] = $filter_type; }
if ($filter_search !== '') {
    $where[]  = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.student_id LIKE ? OR al.rfid_uid LIKE ?)";
    $like     = '%' . $filter_search . '%';
    $params   = array_merge($params, [$like, $like, $like, $like]);
}
$whereSQL = implode(' AND ', $where);

$stmt = $pdo->prepare("SELECT al.id, al.rfid_uid, al.attendance_type, al.scan_method, al.scan_time,
    s.student_id AS student_code, s.first_name, s.last_name, s.course, s.year_level, s.section,
    asess.session_name
    FROM attendance_logs al
    JOIN students s ON al.student_id = s.id
    JOIN attendance_sessions asess ON al.session_id = asess.id
    WHERE $whereSQL ORDER BY al.scan_time DESC");
$stmt->execute($params);
$logs = $stmt->fetchAll();

$sessionsSql = "SELECT id, session_name FROM attendance_sessions";
if ($sessionDeptFilter[0] !== '') $sessionsSql .= " WHERE {$sessionDeptFilter[0]}";
$sessionsSql .= " ORDER BY created_at DESC";
$sessStmt = $pdo->prepare($sessionsSql);
$sessStmt->execute($sessionDeptFilter[1]);
$sessions = $sessStmt->fetchAll();

$totalIn  = count(array_filter($logs, fn($l) => $l['attendance_type'] === 'IN'));
$totalOut = count(array_filter($logs, fn($l) => $l['attendance_type'] === 'OUT'));
?>

<style>
    .page-wrap { padding: 1.5rem; font-family: 'Sora', sans-serif; background: #f8fafc; min-height: 100%; }

    /* Header */
    .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .page-header h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
    .page-header p  { font-size: 0.8125rem; color: #94a3b8; margin: 0.25rem 0 0; }

    .header-right { display: flex; align-items: center; gap: 0.75rem; }

    .live-badge {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.4rem 0.875rem; border-radius: 2rem;
        background: #f0fdf4; border: 1px solid #bbf7d0;
        font-size: 0.75rem; font-weight: 700; color: #16a34a;
        letter-spacing: 0.05em;
    }
    .live-dot { width: 7px; height: 7px; border-radius: 50%; background: #22c55e; animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

    .btn-outline {
        display: inline-flex; align-items: center; gap: 0.375rem;
        padding: 0.5rem 1rem; border-radius: 0.75rem;
        font-size: 0.8125rem; font-weight: 600;
        color: #475569; background: #fff;
        border: 1px solid #e2e8f0; text-decoration: none;
        transition: border-color 0.15s, color 0.15s;
    }
    .btn-outline:hover { border-color: #94a3b8; color: #0f172a; }
    .btn-outline svg  { width: 14px; height: 14px; stroke: currentColor; }

    /* Filter card */
    .filter-card {
        background: #fff; border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.25rem;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 0.6fr 1fr auto;
        gap: 0.875rem;
        align-items: end;
    }

    @media (max-width: 768px) { .filter-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 480px) { .filter-grid { grid-template-columns: 1fr; } }

    .filter-group { display: flex; flex-direction: column; gap: 0.375rem; }

    .filter-group label {
        font-size: 0.75rem; font-weight: 600;
        color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;
    }

    input[type="date"],
    input[type="text"],
    select {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-family: 'Sora', sans-serif;
        font-size: 0.8125rem;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        -webkit-appearance: none;
    }

    input:focus, select:focus {
        border-color: #fb8500;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(251,133,0,0.1);
    }

    select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 13px;
        padding-right: 2.25rem;
        cursor: pointer;
    }

    .filter-actions { display: flex; gap: 0.5rem; }

    .btn-filter {
        padding: 0.625rem 1.125rem;
        border-radius: 0.75rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.875rem; font-weight: 700;
        border: none; cursor: pointer;
        display: inline-flex; align-items: center; gap: 0.375rem;
        transition: background 0.15s, box-shadow 0.15s;
    }

    .btn-filter.primary { background: #fb8500; color: #fff; }
    .btn-filter.primary:hover { background: #e07600; box-shadow: 0 4px 10px rgba(251,133,0,0.25); }
    .btn-filter.secondary { background: #f1f5f9; color: #475569; }
    .btn-filter.secondary:hover { background: #e2e8f0; }
    .btn-filter svg { width: 14px; height: 14px; stroke: currentColor; }

    /* Summary badges */
    .summary-row { display: flex; flex-wrap: wrap; gap: 0.625rem; margin-bottom: 1.25rem; }

    .summary-badge {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.5rem 1rem; border-radius: 0.75rem;
        font-size: 0.8125rem; font-weight: 600;
        border: 1px solid;
    }

    .summary-badge svg { width: 14px; height: 14px; stroke: currentColor; }
    .summary-badge.total   { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
    .summary-badge.in      { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .summary-badge.out     { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .summary-badge.date    { background: #f8fafc; color: #475569; border-color: #e2e8f0; }

    /* Table card */
    .table-card {
        background: #fff; border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .table-wrap { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }

    thead tr { background: #f8fafc; border-bottom: 1px solid #f1f5f9; }

    thead th {
        padding: 0.75rem 1.125rem;
        text-align: left;
        font-size: 0.6875rem; font-weight: 600;
        text-transform: uppercase; letter-spacing: 0.07em;
        color: #94a3b8; white-space: nowrap;
    }

    tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.1s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #fafafa; }
    tbody tr.new-row { animation: highlightNew 2.5s ease-out; }

    @keyframes highlightNew { 0% { background: #d1fae5; } 100% { background: transparent; } }

    tbody td { padding: 0.75rem 1.125rem; color: #334155; vertical-align: middle; }

    .row-num { font-size: 0.75rem; color: #cbd5e1; font-weight: 600; }

    .student-cell { display: flex; align-items: center; gap: 0.625rem; }
    .avatar {
        width: 1.875rem; height: 1.875rem; border-radius: 50%;
        background: #caf0f8; display: flex; align-items: center; justify-content: center;
        font-size: 0.6875rem; font-weight: 700; color: #0369a1; flex-shrink: 0;
    }
    .student-name { font-weight: 600; color: #0f172a; }
    .student-id-code { font-size: 0.75rem; color: #94a3b8; font-family: 'Courier New', monospace; }

    .course-cell .course { font-weight: 500; }
    .course-cell .yr-sec { font-size: 0.75rem; color: #94a3b8; }

    .rfid-code {
        font-family: 'Courier New', monospace; font-size: 0.75rem;
        background: #f1f5f9; border: 1px solid #e2e8f0;
        padding: 0.2rem 0.5rem; border-radius: 0.375rem;
        color: #475569;
    }

    .badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.625rem; border-radius: 2rem;
        font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.04em;
        border: 1px solid;
    }
    .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
    .badge.in   { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .badge.out  { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .badge.method { background: #f8fafc; color: #475569; border-color: #e2e8f0; }

    .scan-time .date { font-weight: 500; }
    .scan-time .time { font-size: 0.75rem; color: #94a3b8; }

    /* Empty state */
    .empty-state {
        padding: 4rem 1.5rem; text-align: center;
        display: flex; flex-direction: column; align-items: center; gap: 0.75rem;
    }
    .empty-icon { width: 3rem; height: 3rem; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; }
    .empty-icon svg { width: 1.375rem; height: 1.375rem; stroke: #94a3b8; }
    .empty-state p { font-size: 0.875rem; color: #94a3b8; font-weight: 500; margin: 0; }
</style>

<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1>Attendance Logs</h1>
            <p><?= date('l, F j, Y', strtotime($filter_date)) ?> · Live tracking</p>
        </div>
        <div class="header-right">
            <div class="live-badge" id="live-badge">
                <span class="live-dot"></span> LIVE
            </div>
            <a href="attendance_reports.php" class="btn-outline">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Reports
            </a>
        </div>
    </div>

    <div class="filter-card">
        <form method="GET">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                </div>
                <div class="filter-group">
                    <label>Session</label>
                    <select name="session_id">
                        <option value="0">All Sessions</option>
                        <?php foreach ($sessions as $sess): ?>
                            <option value="<?= $sess['id'] ?>" <?= $filter_session == $sess['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($sess['session_name']) ?>
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
                    <label>Search</label>
                    <input type="text" name="search" placeholder="Name, Student ID, RFID…" value="<?= htmlspecialchars($filter_search) ?>">
                </div>
                <div class="filter-actions">
                    <button type="submit" class="btn-filter primary">
                        <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                    <a href="attendance_logs.php" class="btn-filter secondary">
                        <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                </div>
            </div>
        </form>
    </div>

    <div class="summary-row">
        <span class="summary-badge total">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
            Total: <?= count($logs) ?>
        </span>
        <span class="summary-badge in">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            IN: <?= $totalIn ?>
        </span>
        <span class="summary-badge out">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            OUT: <?= $totalOut ?>
        </span>
        <span class="summary-badge date">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <?= date('F d, Y', strtotime($filter_date)) ?>
        </span>
    </div>

    <div class="table-card">
        <?php if (empty($logs)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                <p>No attendance logs for the selected filters.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Student</th>
                            <th>Course / Year & Sec</th>
                            <th>RFID UID</th>
                            <th>Session</th>
                            <th>Type</th>
                            <th>Method</th>
                            <th>Scan Time</th>
                        </tr>
                    </thead>
                    <tbody id="logs-tbody">
                        <?php foreach ($logs as $i => $log):
                            $dt = new DateTime($log['scan_time']);
                        ?>
                        <tr data-log-id="<?= $log['id'] ?>">
                            <td><span class="row-num"><?= $i + 1 ?></span></td>
                            <td>
                                <div class="student-cell">
                                    <div class="avatar"><?= strtoupper(substr($log['first_name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="student-name"><?= htmlspecialchars($log['first_name'] . ' ' . $log['last_name']) ?></div>
                                        <div class="student-id-code"><?= htmlspecialchars($log['student_code']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="course-cell">
                                <div class="course"><?= htmlspecialchars($log['course']) ?></div>
                                <div class="yr-sec"><?= htmlspecialchars($log['year_level'] . ' — ' . $log['section']) ?></div>
                            </td>
                            <td><span class="rfid-code"><?= htmlspecialchars($log['rfid_uid']) ?></span></td>
                            <td><?= htmlspecialchars($log['session_name']) ?></td>
                            <td>
                                <span class="badge <?= $log['attendance_type'] === 'IN' ? 'in' : 'out' ?>">
                                    <span class="badge-dot"></span>
                                    <?= htmlspecialchars($log['attendance_type']) ?>
                                </span>
                            </td>
                            <td><span class="badge method"><?= htmlspecialchars($log['scan_method']) ?></span></td>
                            <td class="scan-time">
                                <div class="date"><?= $dt->format('M d, Y') ?></div>
                                <div class="time"><?= $dt->format('h:i:s A') ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
(function () {
    let knownIds = new Set([...document.querySelectorAll('#logs-tbody tr[data-log-id]')].map(r => r.dataset.logId));
    const pollDate    = '<?= $filter_date ?>';
    const pollSession = '<?= $filter_session ?>';
    const pollType    = '<?= $filter_type ?>';

    async function poll() {
        try {
            const qs  = new URLSearchParams({ date: pollDate, session_id: pollSession, type: pollType, format: 'json' });
            const res = await fetch('attendance_logs_ajax.php?' + qs);
            if (!res.ok) return;
            const data = await res.json();
            let newCount = 0;
            const tbody = document.getElementById('logs-tbody');
            if (!tbody) return;
            data.forEach(log => {
                if (knownIds.has(String(log.id))) return;
                knownIds.add(String(log.id));
                newCount++;
                const initial = (log.full_name || '?')[0].toUpperCase();
                const tr = document.createElement('tr');
                tr.dataset.logId = log.id;
                tr.className = 'new-row';
                tr.innerHTML = `
                    <td><span class="row-num">—</span></td>
                    <td>
                        <div class="student-cell">
                            <div class="avatar">${initial}</div>
                            <div>
                                <div class="student-name">${esc(log.full_name)}</div>
                                <div class="student-id-code">${esc(log.student_code)}</div>
                            </div>
                        </div>
                    </td>
                    <td class="course-cell">
                        <div class="course">${esc(log.course)}</div>
                        <div class="yr-sec">${esc(log.year_level + ' — ' + log.section)}</div>
                    </td>
                    <td><span class="rfid-code">${esc(log.rfid_uid)}</span></td>
                    <td>${esc(log.session_name)}</td>
                    <td><span class="badge ${log.attendance_type === 'IN' ? 'in' : 'out'}"><span class="badge-dot"></span>${esc(log.attendance_type)}</span></td>
                    <td><span class="badge method">${esc(log.scan_method)}</span></td>
                    <td class="scan-time"><div class="date">${esc(log.scan_date)}</div><div class="time">${esc(log.scan_time_fmt)}</div></td>
                `;
                tbody.prepend(tr);
            });
            if (newCount > 0) {
                const badge = document.getElementById('live-badge');
                badge.style.background = '#fef2f2';
                badge.style.color      = '#dc2626';
                badge.style.borderColor = '#fecaca';
                setTimeout(() => {
                    badge.style.background   = '#f0fdf4';
                    badge.style.color        = '#16a34a';
                    badge.style.borderColor  = '#bbf7d0';
                }, 900);
            }
        } catch (e) {}
    }

    setInterval(poll, 5000);

    function esc(s) {
        return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }
})();
</script>

<?php render_footer(); ?>