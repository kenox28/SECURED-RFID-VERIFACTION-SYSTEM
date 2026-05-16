<?php
$page_title = 'Activity Logs';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../backend/admin/manage_admin_actions.php';

if (!is_super_admin()) {
    header('Location: dashboard.php');
    exit();
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'delete_log') {
        $log_id = intval($_POST['log_id'] ?? 0);

        if ($log_id <= 0) {
            $error = 'Invalid log selected.';
        } elseif (!delete_activity_log($log_id)) {
            $error = 'Unable to delete activity log.';
        } else {
            $_SESSION['success'] = 'Activity log deleted successfully.';
            header('Location: activity_logs.php');
            exit();
        }
    }
}

$activity_logs = get_activity_logs(100);

render_header($page_title);
?>
<div class="row mb-4">
    <div class="col-12">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <div class="card">
            <div class="card-header">
                <h5>Activity Logs</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Activity</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($activity_logs)): ?>
                                <tr><td colspan="5" class="text-muted">No activity logs found.</td></tr>
                            <?php else: ?>
                                <?php foreach ($activity_logs as $log): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($log['role'] ?? 'Unknown'); ?></td>
                                        <td><?php echo htmlspecialchars($log['activity']); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteLog(<?php echo $log['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteLogModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this activity log entry? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="form_type" value="delete_log">
                    <input type="hidden" id="deleteLogId" name="log_id" value="">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteLog(logId) {
    document.getElementById('deleteLogId').value = logId;
    new bootstrap.Modal(document.getElementById('deleteLogModal')).show();
}
</script>

<?php render_footer(); ?>
