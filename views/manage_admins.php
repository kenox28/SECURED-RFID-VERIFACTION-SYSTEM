<?php
$page_title = 'Manage Admins';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../backend/admin/manage_admin_actions.php';

if (!is_super_admin()) {
    header('Location: dashboard.php');
    exit();
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$editAdmin = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editAdmin = get_admin_by_id((int)$_GET['id']);
    if (!$editAdmin) {
        header('Location: manage_admins.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;

        if ($username === '' || $fullname === '' || $password === '' || $confirmPassword === '') {
            $error = 'Please fill in all required fields.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Password and confirmation do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($role !== 'super_admin' && $role !== 'admin') {
            $error = 'Invalid role selected.';
        } elseif ($role === 'admin' && (!$department_id || $department_id <= 0)) {
            $error = 'Please select a department for department admins.';
        } elseif (!create_admin($username, $fullname, $password, $role, $department_id)) {
            $error = 'Unable to create admin. Username may already exist.';
        } else {
            $_SESSION['success'] = 'Admin account created successfully.';
            log_activity("[super_admin] {$_SESSION['username']} created admin {$username}");
            header('Location: manage_admins.php');
            exit();
        }
    }

    if ($formType === 'update_admin') {
        $admin_id = intval($_POST['admin_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $password = $_POST['password'] ?? null;
        $confirmPassword = $_POST['confirm_password'] ?? null;
        $role = $_POST['role'] ?? 'admin';
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
        $status = $_POST['status'] ?? 'active';

        if ($admin_id <= 0 || $username === '' || $fullname === '') {
            $error = 'Please fill in all required fields.';
        } elseif ($role !== 'super_admin' && $role !== 'admin') {
            $error = 'Invalid role selected.';
        } elseif ($role === 'admin' && (!$department_id || $department_id <= 0)) {
            $error = 'Please select a department for department admins.';
        } elseif ($password !== null && $password !== '' && $password !== $confirmPassword) {
            $error = 'Password and confirmation do not match.';
        } elseif ($password !== null && $password !== '' && strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif (!update_admin($admin_id, $username, $fullname, $password, $role, $department_id, $status)) {
            $error = 'Unable to update admin. Username may already exist.';
        } else {
            $_SESSION['success'] = 'Admin account updated successfully.';
            log_activity("[super_admin] {$_SESSION['username']} updated admin {$username}");
            header('Location: manage_admins.php');
            exit();
        }
    }

    if ($formType === 'delete_admin') {
        $admin_id = intval($_POST['admin_id'] ?? 0);

        if ($admin_id === $_SESSION['user_id']) {
            $error = 'You cannot delete your own account while logged in.';
        } elseif ($admin_id <= 0) {
            $error = 'Invalid admin selected.';
        } elseif (!delete_admin($admin_id)) {
            $error = 'Unable to delete admin account.';
        } else {
            $_SESSION['success'] = 'Admin account deleted successfully.';
            log_activity("[super_admin] {$_SESSION['username']} deleted admin ID {$admin_id}");
            header('Location: manage_admins.php');
            exit();
        }
    }
}

$admins = get_admins();
$departments = get_departments();

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
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><?php echo $editAdmin ? 'Edit Admin' : 'Create Admin'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($editAdmin): ?>
                        <input type="hidden" name="form_type" value="update_admin">
                        <input type="hidden" name="admin_id" value="<?php echo $editAdmin['id']; ?>">
                    <?php else: ?>
                        <input type="hidden" name="form_type" value="create_admin">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" value="<?php echo htmlspecialchars($editAdmin['username'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" class="form-control" name="fullname" value="<?php echo htmlspecialchars($editAdmin['fullname'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $editAdmin ? 'New Password (leave blank to keep current)' : 'Password'; ?></label>
                        <input type="password" class="form-control" name="password" <?php echo $editAdmin ? '' : 'required'; ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $editAdmin ? 'Confirm Password' : 'Confirm Password'; ?></label>
                        <input type="password" class="form-control" name="confirm_password" <?php echo $editAdmin ? '' : 'required'; ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="admin" <?php echo (isset($editAdmin['role']) && $editAdmin['role'] === 'admin') ? 'selected' : ''; ?>>Department Admin</option>
                            <option value="super_admin" <?php echo (isset($editAdmin['role']) && $editAdmin['role'] === 'super_admin') ? 'selected' : ''; ?>>Super Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Department</label>
                        <select class="form-select" name="department_id">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>" <?php echo (isset($editAdmin['department_id']) && $editAdmin['department_id'] == $dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Leave empty for super admin accounts.</div>
                    </div>
                    <?php if ($editAdmin): ?>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status" required>
                                <option value="active" <?php echo $editAdmin['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $editAdmin['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary"><?php echo $editAdmin ? 'Update Admin' : 'Create Admin'; ?></button>
                    <?php if ($editAdmin): ?>
                        <a href="manage_admins.php" class="btn btn-secondary ms-2">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Admin Accounts</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($admins)): ?>
                                <tr><td colspan="7" class="text-muted">No admins registered.</td></tr>
                            <?php else: ?>
                                <?php foreach ($admins as $admin): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['fullname']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['role']); ?></td>
                                        <td><?php echo htmlspecialchars($admin['department_name'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($admin['status']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                                        <td>
                                            <a href="manage_admins.php?action=edit&id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                            <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" class="btn btn-sm btn-danger" onclick="confirmDeleteAdmin(<?php echo $admin['id']; ?>)">Delete</button>
                                            <?php endif; ?>
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

<div class="modal fade" id="deleteAdminModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this admin account? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="form_type" value="delete_admin">
                    <input type="hidden" id="deleteAdminId" name="admin_id" value="">
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteAdmin(adminId) {
    document.getElementById('deleteAdminId').value = adminId;
    new bootstrap.Modal(document.getElementById('deleteAdminModal')).show();
}
</script>

<?php render_footer(); ?>
