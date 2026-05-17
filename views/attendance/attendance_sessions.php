<?php
require_once __DIR__ . '/../layout.php';
$page_title = 'Attendance Sessions';
render_header($page_title);

$deptFilter = get_department_filter('asess', true);
$whereSQL = $deptFilter[0] ? "WHERE {$deptFilter[0]}" : '';
$stmt = $pdo->prepare("SELECT asess.*, d.department_name FROM attendance_sessions asess LEFT JOIN departments d ON asess.department_id = d.id {$whereSQL} ORDER BY asess.created_at DESC");
$stmt->execute($deptFilter[1]);
$sessions = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);
?>
<div class="container-fluid px-4 mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i>Attendance Sessions</h2>
        <a href="create_session.php" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i> Create New Session</a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($flash); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($flash_error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <div class="alert alert-info">No sessions found. <a href="create_session.php">Create one now.</a></div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Session Name</th>
                            <th>Department</th>
                            <th>Type</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th class="text-center" style="width:230px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $i => $session): ?>
                            <tr class="<?php echo $session['status'] === 'ACTIVE' ? 'table-success' : ''; ?>">
                                <td class="text-muted small"><?php echo $i + 1; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($session['session_name']); ?></td>
                                <td><?php echo htmlspecialchars($session['department_name'] ?? 'General'); ?></td>
                                <td>
                                    <span class="badge <?php echo $session['attendance_type'] === 'IN' ? 'bg-primary' : 'bg-warning text-dark'; ?>">
                                        <?php echo htmlspecialchars($session['attendance_type']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('h:i A', strtotime($session['start_time'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($session['end_time'])); ?></td>
                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($session['created_at'])); ?></td>
                                <td>
                                    <?php if ($session['status'] === 'ACTIVE'): ?>
                                        <span class="badge bg-success"><i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle"></i>ACTIVE</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">INACTIVE</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center flex-wrap">
                                        <?php if ($session['status'] === 'INACTIVE'): ?>
                                            <form method="POST" action="start_session.php" onsubmit="return confirm('Start this session? All other active sessions will be stopped.')">
                                                <input type="hidden" name="id" value="<?php echo $session['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" title="Start"><i class="bi bi-play-fill"></i> Start</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="stop_session.php" onsubmit="return confirm('Stop this session?')">
                                                <input type="hidden" name="id" value="<?php echo $session['id']; ?>">
                                                <button type="submit" class="btn btn-secondary btn-sm" title="Stop"><i class="bi bi-stop-fill"></i> Stop</button>
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
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('delete-session-name').textContent = name;
    document.getElementById('delete-session-id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php render_footer(); ?>