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
<style>
    .logs-wrap {
        padding: 1.5rem;
        font-family: 'Sora', sans-serif;
        background: #f8fafc;
        min-height: 100%;
    }

    .page-header {
        margin-bottom: 1.75rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header h1 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.02em;
        margin: 0;
    }

    .page-header p {
        font-size: 0.8125rem;
        color: #94a3b8;
        margin: 0.25rem 0 0;
    }

    .logs-card {
        background: #fff;
        border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .card-header-custom {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-header-custom h2 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .alert-custom {
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 500;
        margin-bottom: 1.5rem;
        border: 1px solid;
    }

    .alert-success-custom {
        background: #ecfdf5;
        border-color: #d1fae5;
        color: #047857;
    }

    .alert-danger-custom {
        background: #fef2f2;
        border-color: #fecaca;
        color: #dc2626;
    }

    .table-wrap {
        overflow-x: auto;
    }

    .logs-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 950px;
    }

    .logs-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    .logs-table thead th {
        padding: 0.875rem 1.5rem;
        text-align: left;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
        white-space: nowrap;
    }

    .logs-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .logs-table tbody tr:hover {
        background: #f8fafc;
    }

    .logs-table tbody td {
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        color: #334155;
        vertical-align: middle;
    }

    .time-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.45rem 0.75rem;
        border-radius: 0.75rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        font-size: 0.75rem;
        font-weight: 600;
        color: #475569;
        white-space: nowrap;
    }

    .user-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .user-avatar {
        width: 2.1rem;
        height: 2.1rem;
        border-radius: 50%;
        background: #fff3e0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        color: #fb8500;
        text-transform: uppercase;
        flex-shrink: 0;
    }

    .username-text {
        font-weight: 700;
        color: #0f172a;
    }

    .role-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.4rem 0.75rem;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .role-super {
        background: #dbeafe;
        color: #1d4ed8;
    }

    .role-admin {
        background: #fff3e0;
        color: #fb8500;
    }

    .activity-text {
        color: #475569;
        line-height: 1.5;
        min-width: 280px;
    }

    .btn-delete {
        border: none;
        border-radius: 0.75rem;
        padding: 0.55rem 0.95rem;
        background: #fef2f2;
        color: #dc2626;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
    }

    .btn-delete:hover {
        background: #fee2e2;
    }

    .empty-state {
        padding: 4rem 1.5rem;
        text-align: center;
    }

    .empty-state p {
        color: #94a3b8;
        font-size: 0.875rem;
        font-weight: 500;
        margin: 0;
    }

    .modal-content {
        border-radius: 1.25rem;
        border: none;
        overflow: hidden;
    }

    .modal-header {
        border-bottom: 1px solid #f1f5f9;
        padding: 1.25rem 1.5rem;
    }

    .modal-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-weight: 700;
        color: #0f172a;
    }

    .modal-body {
        padding: 1.5rem;
        color: #475569;
        font-size: 0.875rem;
    }

    .modal-footer {
        border-top: 1px solid #f1f5f9;
        padding: 1rem 1.5rem;
    }

    .btn-modal-secondary {
        border: none;
        background: #f1f5f9;
        color: #475569;
        border-radius: 0.75rem;
        padding: 0.7rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .btn-modal-danger {
        border: none;
        background: #dc2626;
        color: #fff;
        border-radius: 0.75rem;
        padding: 0.7rem 1rem;
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .btn-modal-danger:hover {
        background: #b91c1c;
    }
</style>

<div class="logs-wrap">

    <div class="page-header">
        <div>
            <h1>Activity Logs</h1>
            <p><?= date('l, F j, Y') ?> · Recent system activity records</p>
        </div>
    </div>

    <?php if (!empty($success)): ?>
        <div class="alert-custom alert-success-custom">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert-custom alert-danger-custom">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="logs-card">

        <div class="card-header-custom">
            <h2>System Activity Logs</h2>
        </div>

        <?php if (empty($activity_logs)): ?>

            <div class="empty-state">
                <p>No activity logs found.</p>
            </div>

        <?php else: ?>

            <div class="table-wrap">

                <table class="logs-table">

                    <thead>
                        <tr>
                            <!-- <th>Time</th> -->
                            <th>User</th>
                            <th>Role</th>
                            <th>Activity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($activity_logs as $log): ?>

                        <tr>



                            <td>

                                <div class="user-info">

                                    <div class="user-avatar">
                                        <?= strtoupper(substr($log['username'] ?? 'U', 0, 1)) ?>
                                    </div>

                                    <div class="username-text">
                                        <?= htmlspecialchars($log['username'] ?? 'Unknown') ?>
                                    </div>

                                </div>

                            </td>

                            <td>

                                <span class="role-badge <?= ($log['role'] ?? '') === 'super_admin' ? 'role-super' : 'role-admin' ?>">

                                    <?= ($log['role'] ?? '') === 'super_admin'
                                        ? 'Super Admin'
                                        : 'Department Admin' ?>

                                </span>

                            </td>

                            <td>
                                <div class="activity-text">
                                    <?= htmlspecialchars($log['activity'] ?? '') ?>
                                </div>
                            </td>

                            <td>

                                <button type="button"
                                        class="btn-delete"
                                        onclick="confirmDeleteLog(<?= $log['id'] ?>)">
                                    Delete
                                </button>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>

<!-- DELETE MODAL -->
<div class="modal fade" id="deleteLogModal" tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title">Delete Activity Log</h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>

            </div>

            <div class="modal-body">
                Are you sure you want to permanently delete this activity log?
            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-modal-secondary"
                        data-bs-dismiss="modal">
                    Cancel
                </button>

                <form method="POST">

                    <input type="hidden"
                           name="form_type"
                           value="delete_log">

                    <input type="hidden"
                           id="deleteLogId"
                           name="log_id">

                    <button class="btn-modal-danger">
                        Delete Log
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>

<script>
function confirmDeleteLog(id) {
    document.getElementById('deleteLogId').value = id;
    new bootstrap.Modal(document.getElementById('deleteLogModal')).show();
}
</script>

<?php render_footer(); ?>