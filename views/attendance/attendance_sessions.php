<?php
require_once __DIR__ . '/../layout.php';
$page_title = 'Attendance Sessions';
render_header($page_title);

$deptFilter = get_department_filter('asess', true);
$whereSQL   = $deptFilter[0] ? "WHERE {$deptFilter[0]}" : '';
$stmt = $pdo->prepare("SELECT asess.*, d.department_name FROM attendance_sessions asess LEFT JOIN departments d ON asess.department_id = d.id {$whereSQL} ORDER BY asess.created_at DESC");
$stmt->execute($deptFilter[1]);
$sessions = $stmt->fetchAll();

$flash       = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);
?>

<style>
    .page-wrap { padding: 1.5rem; font-family: 'Sora', sans-serif; background: #f8fafc; min-height: 100%; }

    /* Header */
    .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .page-header h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
    .page-header p  { font-size: 0.8125rem; color: #94a3b8; margin: 0.25rem 0 0; }

    .btn-primary {
        display: inline-flex; align-items: center; gap: 0.5rem;
        padding: 0.625rem 1.25rem; border-radius: 0.75rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.875rem; font-weight: 700;
        background: #fb8500; color: #fff; border: none;
        text-decoration: none; cursor: pointer;
        transition: background 0.15s, box-shadow 0.15s;
    }
    .btn-primary:hover { background: #e07600; box-shadow: 0 4px 12px rgba(251,133,0,0.3); }
    .btn-primary svg  { width: 16px; height: 16px; stroke: #fff; }

    /* Alerts */
    .alert { border-radius: 0.875rem; padding: 0.875rem 1rem; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 0.75rem; }
    .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; }
    .alert-error   { background: #fef2f2; border: 1px solid #fecaca; }
    .alert svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; margin-top: 0.0625rem; }
    .alert-success svg { stroke: #16a34a; }
    .alert-error   svg { stroke: #dc2626; }
    .alert p { font-size: 0.8125rem; font-weight: 500; margin: 0; }
    .alert-success p { color: #16a34a; }
    .alert-error   p { color: #dc2626; }

    /* Empty state */
    .empty-card {
        background: #fff; border-radius: 1.25rem;
        border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        padding: 3.5rem 1.5rem; text-align: center;
        display: flex; flex-direction: column; align-items: center; gap: 1rem;
    }
    .empty-icon { width: 3rem; height: 3rem; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; }
    .empty-icon svg { width: 1.375rem; height: 1.375rem; stroke: #94a3b8; }
    .empty-card p { font-size: 0.875rem; color: #94a3b8; margin: 0; }
    .empty-card a { color: #fb8500; font-weight: 600; text-decoration: none; }

    /* Table card */
    .table-card { background: #fff; border-radius: 1.25rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; }
    .table-card-header { padding: 1.125rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; }
    .table-card-header h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9375rem; font-weight: 700; color: #0f172a; margin: 0; }
    .session-count { font-size: 0.75rem; font-weight: 600; color: #94a3b8; background: #f8fafc; border: 1px solid #e2e8f0; padding: 0.25rem 0.625rem; border-radius: 2rem; }

    .table-wrap { overflow-x: auto; }

    table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
    thead tr { background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
    thead th { padding: 0.75rem 1.25rem; text-align: left; font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.07em; color: #94a3b8; white-space: nowrap; }
    tbody tr { border-bottom: 1px solid #f8fafc; transition: background 0.1s; }
    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #fafafa; }
    tbody tr.is-active { background: #f0fdf4; }
    tbody tr.is-active:hover { background: #dcfce7; }
    tbody td { padding: 0.8125rem 1.25rem; color: #334155; vertical-align: middle; }

    .row-num { font-size: 0.75rem; color: #cbd5e1; font-weight: 600; }

    .session-name { font-weight: 600; color: #0f172a; }

    .dept-badge {
        display: inline-flex; align-items: center;
        padding: 0.2rem 0.625rem; border-radius: 2rem;
        font-size: 0.6875rem; font-weight: 600;
        background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0;
    }

    .badge {
        display: inline-flex; align-items: center; gap: 0.3rem;
        padding: 0.25rem 0.625rem; border-radius: 2rem;
        font-size: 0.6875rem; font-weight: 700; letter-spacing: 0.04em;
        border: 1px solid;
    }
    .badge-dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
    .badge.in      { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .badge.out     { background: #fffbeb; color: #b45309; border-color: #fde68a; }
    .badge.active  { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .badge.inactive{ background: #f8fafc; color: #94a3b8; border-color: #e2e8f0; }

    .time-cell { font-weight: 500; color: #334155; }
    .date-cell { font-size: 0.75rem; color: #94a3b8; }

    /* Action buttons */
    .action-cell { display: flex; gap: 0.5rem; }

    .btn-action {
        display: inline-flex; align-items: center; gap: 0.375rem;
        padding: 0.4375rem 0.875rem; border-radius: 0.625rem;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.75rem; font-weight: 700;
        border: 1px solid; cursor: pointer;
        transition: all 0.15s; text-decoration: none;
        white-space: nowrap;
    }
    .btn-action svg { width: 13px; height: 13px; stroke: currentColor; }

    .btn-start  { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }
    .btn-start:hover  { background: #16a34a; color: #fff; border-color: #16a34a; }

    .btn-stop   { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
    .btn-stop:hover   { background: #64748b; color: #fff; border-color: #64748b; }
</style>

<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1>Attendance Sessions</h1>
            <p>Manage and control attendance sessions</p>
        </div>
        <a href="create_session.php" class="btn-primary">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create New Session
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p><?= htmlspecialchars($flash) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
        <div class="alert alert-error">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p><?= htmlspecialchars($flash_error) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <div class="empty-card">
            <div class="empty-icon">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <p>No sessions found. <a href="create_session.php">Create one now.</a></p>
        </div>
    <?php else: ?>
        <div class="table-card">
            <div class="table-card-header">
                <h2>All Sessions</h2>
                <span class="session-count"><?= count($sessions) ?> session<?= count($sessions) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Session Name</th>
                            <th>Department</th>
                            <th>Type</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $i => $session): ?>
                        <tr class="<?= $session['status'] === 'ACTIVE' ? 'is-active' : '' ?>">
                            <td><span class="row-num"><?= $i + 1 ?></span></td>
                            <td><span class="session-name"><?= htmlspecialchars($session['session_name']) ?></span></td>
                            <td><span class="dept-badge"><?= htmlspecialchars($session['department_name'] ?? 'General') ?></span></td>
                            <td>
                                <span class="badge <?= $session['attendance_type'] === 'IN' ? 'in' : 'out' ?>">
                                    <span class="badge-dot"></span>
                                    <?= htmlspecialchars($session['attendance_type']) ?>
                                </span>
                            </td>
                            <td><span class="time-cell"><?= date('h:i A', strtotime($session['start_time'])) ?></span></td>
                            <td><span class="time-cell"><?= date('h:i A', strtotime($session['end_time'])) ?></span></td>
                            <td><span class="date-cell"><?= date('M d, Y', strtotime($session['created_at'])) ?></span></td>
                            <td>
                                <?php if ($session['status'] === 'ACTIVE'): ?>
                                    <span class="badge active"><span class="badge-dot"></span> Active</span>
                                <?php else: ?>
                                    <span class="badge inactive"><span class="badge-dot"></span> Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-cell">
                                    <?php if ($session['status'] === 'INACTIVE'): ?>
                                        <form method="POST" action="start_session.php"
                                            onsubmit="return confirm('Start this session? All other active sessions will be stopped.')">
                                            <input type="hidden" name="id" value="<?= $session['id'] ?>">
                                            <button type="submit" class="btn-action btn-start">
                                                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                Start
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="stop_session.php"
                                            onsubmit="return confirm('Stop this session?')">
                                            <input type="hidden" name="id" value="<?= $session['id'] ?>">
                                            <button type="submit" class="btn-action btn-stop">
                                                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10h6v4H9z"/></svg>
                                                Stop
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php render_footer(); ?>