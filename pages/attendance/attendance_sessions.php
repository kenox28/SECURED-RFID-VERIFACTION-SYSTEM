<?php
// admin/attendance/attendance_sessions.php
require_once '../../includes/header.php';

$page_title = 'Attendance Sessions';

// Flash messages
$flash       = '';
$flash_error = '';
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
if (!empty($_SESSION['flash_error'])) {
    $flash_error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Fetch ALL sessions — no status filter
$stmt     = $pdo->query("SELECT * FROM attendance_sessions ORDER BY created_at DESC");
$sessions = $stmt->fetchAll();
?>

<div class="container-fluid px-4 mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">
            <i class="bi bi-calendar-check me-2 text-primary"></i>Attendance Sessions
        </h2>
        <a href="create_session.php" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Create New Session
        </a>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($flash_error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($flash_error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($sessions)): ?>
        <div class="alert alert-info">
            No sessions found. <a href="create_session.php">Create one now.</a>
        </div>
    <?php else: ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:40px">#</th>
                        <th>Session Name</th>
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
                    <tr class="<?= $session['status'] === 'ACTIVE' ? 'table-success' : '' ?>">
                        <td class="text-muted small"><?= $i + 1 ?></td>
                        <td class="fw-semibold"><?= htmlspecialchars($session['session_name']) ?></td>
                        <td>
                            <span class="badge <?= $session['attendance_type'] === 'IN' ? 'bg-primary' : 'bg-warning text-dark' ?>">
                                <?= htmlspecialchars($session['attendance_type']) ?>
                            </span>
                        </td>
                        <td><?= date('h:i A', strtotime($session['start_time'])) ?></td>
                        <td><?= date('h:i A', strtotime($session['end_time'])) ?></td>
                        <td class="text-muted small"><?= date('M d, Y', strtotime($session['created_at'])) ?></td>
                        <td>
                            <?php if ($session['status'] === 'ACTIVE'): ?>
                                <span class="badge bg-success">
                                    <i class="bi bi-circle-fill me-1" style="font-size:.45rem;vertical-align:middle"></i>ACTIVE
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">INACTIVE</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center flex-wrap">

                                <!-- EDIT -->
                                <a href="edit_session.php?id=<?= $session['id'] ?>"
                                   class="btn btn-outline-warning btn-sm" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <!-- START (only shown for INACTIVE) -->
                                <?php if ($session['status'] === 'INACTIVE'): ?>
                                <form method="POST" action="start_session.php"
                                      onsubmit="return confirm('Start this session?\nAll other active sessions will be stopped.')">
                                    <input type="hidden" name="id" value="<?= $session['id'] ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Start">
                                        <i class="bi bi-play-fill"></i> Start
                                    </button>
                                </form>

                                <!-- STOP (only shown for ACTIVE) -->
                                <?php else: ?>
                                <form method="POST" action="stop_session.php"
                                      onsubmit="return confirm('Stop this session?')">
                                    <input type="hidden" name="id" value="<?= $session['id'] ?>">
                                    <button type="submit" class="btn btn-secondary btn-sm" title="Stop">
                                        <i class="bi bi-stop-fill"></i> Stop
                                    </button>
                                </form>
                                <?php endif; ?>

                                <!-- DELETE -->
                                <button type="button"
                                        class="btn btn-outline-danger btn-sm"
                                        title="Delete"
                                        onclick="confirmDelete(<?= $session['id'] ?>, '<?= htmlspecialchars(addslashes($session['session_name'])) ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>

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

<!-- Delete confirmation modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle me-2"></i>Delete Session
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Delete session <strong id="delete-session-name"></strong>?
                <div class="text-danger small mt-2">
                    <i class="bi bi-exclamation-circle me-1"></i>
                    This will permanently delete all attendance logs for this session.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" action="delete_session.php" style="display:inline">
                    <input type="hidden" name="id" id="delete-session-id" value="">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-1"></i>Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('delete-session-name').textContent = name;
    document.getElementById('delete-session-id').value = id;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>