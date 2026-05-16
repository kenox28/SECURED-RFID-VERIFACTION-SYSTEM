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
        } elseif (!in_array($role, ['super_admin', 'admin'])) {
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
        } elseif (!in_array($role, ['super_admin', 'admin'])) {
            $error = 'Invalid role selected.';
        } elseif ($role === 'admin' && (!$department_id || $department_id <= 0)) {
            $error = 'Please select a department for department admins.';
        } elseif ($password && $password !== $confirmPassword) {
            $error = 'Password and confirmation do not match.';
        } elseif ($password && strlen($password) < 6) {
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
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($success) ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-lg-5 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><?= $editAdmin ? 'Edit Admin' : 'Create Admin' ?></h5>
            </div>

            <div class="card-body">
                <form method="POST">

                    <input type="hidden" name="form_type" value="<?= $editAdmin ? 'update_admin' : 'create_admin' ?>">

                    <?php if ($editAdmin): ?>
                        <input type="hidden" name="admin_id" value="<?= $editAdmin['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Username</label>
                        <input type="text" name="username" class="form-control"
                               value="<?= htmlspecialchars($editAdmin['username'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Full Name</label>
                        <input type="text" name="fullname" class="form-control"
                               value="<?= htmlspecialchars($editAdmin['fullname'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Password</label>
                        <input type="password" name="password" class="form-control"
                               <?= !$editAdmin ? 'required' : '' ?>>
                    </div>

                    <div class="mb-3">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" class="form-control"
                               <?= !$editAdmin ? 'required' : '' ?>>
                    </div>

                    <div class="mb-3">
                        <label>Role</label>
                        <select name="role" class="form-select">
                            <option value="admin" <?= (isset($editAdmin['role']) && $editAdmin['role'] === 'admin') ? 'selected' : '' ?>>
                                Department Admin
                            </option>
                            <option value="super_admin" <?= (isset($editAdmin['role']) && $editAdmin['role'] === 'super_admin') ? 'selected' : '' ?>>
                                Super Admin
                            </option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label>Department</label>
                        <select name="department_id" class="form-select">
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"
                                    <?= (isset($editAdmin['department_id']) && $editAdmin['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept['department_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($editAdmin): ?>
                        <div class="mb-3">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= $editAdmin['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= $editAdmin['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <button class="btn btn-primary">
                        <?= $editAdmin ? 'Update' : 'Create' ?>
                    </button>

                    <?php if ($editAdmin): ?>
                        <a href="manage_admins.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>

                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5>Admin Accounts</h5>
            </div>

            <div class="card-body table-responsive">
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
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['username']) ?></td>
                            <td><?= htmlspecialchars($admin['fullname']) ?></td>
                            <td><?= htmlspecialchars($admin['role']) ?></td>
                            <td><?= htmlspecialchars($admin['department_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($admin['status']) ?></td>

                            <td>
                                <?= !empty($admin['created_at'])
                                    ? date('M d, Y', strtotime($admin['created_at']))
                                    : 'No date' ?>
                            </td>

                            <td>
                                <a href="manage_admins.php?action=edit&id=<?= $admin['id'] ?>"
                                   class="btn btn-sm btn-warning">Edit</a>

                                <?php if ($admin['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger"
                                            onclick="confirmDeleteAdmin(<?= $admin['id'] ?>)">
                                        Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>

                </table>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDeleteAdmin(id) {
    document.getElementById('deleteAdminId').value = id;
    new bootstrap.Modal(document.getElementById('deleteAdminModal')).show();
}
</script>