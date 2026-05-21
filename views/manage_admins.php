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
<style>
    .admin-wrap {
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

    .admin-grid {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 1.5rem;
        align-items: start;
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

    .card-body-custom {
        padding: 1.5rem;
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

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #475569;
    }

    .form-control-custom,
    .form-select-custom {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        background: #fff;
        color: #0f172a;
        transition: all 0.15s ease;
    }

    .form-control-custom:focus,
    .form-select-custom:focus {
        outline: none;
        border-color: #fb8500;
        box-shadow: 0 0 0 4px rgba(251,133,0,0.08);
    }

    .button-group {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .btn-custom {
        border: none;
        border-radius: 0.875rem;
        padding: 0.85rem 1.25rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-primary-custom {
        background: #fb8500;
        color: #fff;
    }

    .btn-primary-custom:hover {
        background: #ea7c00;
        transform: translateY(-1px);
    }

    .btn-secondary-custom {
        background: #f1f5f9;
        color: #475569;
    }

    .btn-secondary-custom:hover {
        background: #e2e8f0;
    }

    .table-wrap {
        overflow-x: auto;
    }

    .admin-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 900px;
    }

    .admin-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    .admin-table thead th {
        padding: 0.875rem 1.5rem;
        text-align: left;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
        white-space: nowrap;
    }

    .admin-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .admin-table tbody tr:hover {
        background: #f8fafc;
    }

    .admin-table tbody td {
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        color: #334155;
        vertical-align: middle;
    }

    .admin-user {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .admin-avatar {
        width: 2.2rem;
        height: 2.2rem;
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

    .role-badge,
    .status-badge {
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

    .status-active {
        background: #dcfce7;
        color: #15803d;
    }

    .status-inactive {
        background: #fee2e2;
        color: #dc2626;
    }

    .action-buttons {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-table {
        border: none;
        border-radius: 0.75rem;
        padding: 0.55rem 0.9rem;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s ease;
        text-decoration: none;
    }

    .btn-edit {
        background: #fff3e0;
        color: #fb8500;
    }

    .btn-edit:hover {
        background: #ffe0b2;
    }

    .btn-delete {
        background: #fef2f2;
        color: #dc2626;
    }

    .btn-delete:hover {
        background: #fee2e2;
    }

    .date-text {
        color: #94a3b8;
        font-size: 0.8125rem;
    }

    @media (max-width: 1100px) {
        .admin-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="admin-wrap">

    <div class="page-header">
        <div>
            <h1>Manage Admins</h1>
            <p><?= date('l, F j, Y') ?> · System administrators management</p>
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

    <div class="admin-grid">

        <div class="admin-card">

            <div class="card-header-custom">
                <h2><?= $editAdmin ? 'Edit Admin' : 'Create Admin' ?></h2>
            </div>

            <div class="card-body-custom">

                <form method="POST">

                    <input type="hidden" name="form_type"
                           value="<?= $editAdmin ? 'update_admin' : 'create_admin' ?>">

                    <?php if ($editAdmin): ?>
                        <input type="hidden" name="admin_id" value="<?= $editAdmin['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text"
                               name="username"
                               class="form-control-custom"
                               value="<?= htmlspecialchars($editAdmin['username'] ?? '') ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text"
                               name="fullname"
                               class="form-control-custom"
                               value="<?= htmlspecialchars($editAdmin['fullname'] ?? '') ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <input type="password"
                               name="password"
                               class="form-control-custom"
                               <?= !$editAdmin ? 'required' : '' ?>>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password"
                               name="confirm_password"
                               class="form-control-custom"
                               <?= !$editAdmin ? 'required' : '' ?>>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select-custom">
                            <option value="admin"
                                <?= (isset($editAdmin['role']) && $editAdmin['role'] === 'admin') ? 'selected' : '' ?>>
                                Department Admin
                            </option>

                            <option value="super_admin"
                                <?= (isset($editAdmin['role']) && $editAdmin['role'] === 'super_admin') ? 'selected' : '' ?>>
                                Super Admin
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department</label>

                        <select name="department_id" class="form-select-custom">
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
                        <div class="form-group">
                            <label class="form-label">Status</label>

                            <select name="status" class="form-select-custom">
                                <option value="active"
                                    <?= $editAdmin['status'] === 'active' ? 'selected' : '' ?>>
                                    Active
                                </option>

                                <option value="inactive"
                                    <?= $editAdmin['status'] === 'inactive' ? 'selected' : '' ?>>
                                    Inactive
                                </option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="button-group">

                        <button class="btn-custom btn-primary-custom">
                            <?= $editAdmin ? 'Update Admin' : 'Create Admin' ?>
                        </button>

                        <?php if ($editAdmin): ?>
                            <a href="manage_admins.php"
                               class="btn-custom btn-secondary-custom">
                                Cancel
                            </a>
                        <?php endif; ?>

                    </div>

                </form>

            </div>
        </div>

        <div class="admin-card">

            <div class="card-header-custom">
                <h2>Admin Accounts</h2>
            </div>

            <div class="table-wrap">

                <table class="admin-table">

                    <thead>
                        <tr>
                            <th>Admin</th>
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

                            <td>
                                <div class="admin-user">

                                    <div class="admin-avatar">
                                        <?= strtoupper(substr($admin['fullname'], 0, 1)) ?>
                                    </div>

                                    <div>
                                        <div style="font-weight: 700; color:#0f172a;">
                                            <?= htmlspecialchars($admin['fullname']) ?>
                                        </div>

                                        <div style="font-size:0.75rem; color:#94a3b8;">
                                            @<?= htmlspecialchars($admin['username']) ?>
                                        </div>
                                    </div>

                                </div>
                            </td>

                            <td>
                                <span class="role-badge <?= $admin['role'] === 'super_admin' ? 'role-super' : 'role-admin' ?>">
                                    <?= $admin['role'] === 'super_admin' ? 'Super Admin' : 'Department Admin' ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($admin['department_name'] ?? 'N/A') ?>
                            </td>

                            <td>
                                <span class="status-badge <?= $admin['status'] === 'active' ? 'status-active' : 'status-inactive' ?>">
                                    <?= htmlspecialchars($admin['status']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="date-text">
                                    <?= !empty($admin['created_at'])
                                        ? date('M d, Y', strtotime($admin['created_at']))
                                        : 'No date' ?>
                                </span>
                            </td>

                            <td>

                                <div class="action-buttons">

                                    <a href="manage_admins.php?action=edit&id=<?= $admin['id'] ?>"
                                       class="btn-table btn-edit">
                                        Edit
                                    </a>

                                    <?php if ($admin['id'] != $_SESSION['user_id']): ?>

                                        <button class="btn-table btn-delete"
                                                onclick="confirmDeleteAdmin(<?= $admin['id'] ?>)">
                                            Delete
                                        </button>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>
<!-- Delete Admin Modal -->
<div class="modal fade" id="deleteAdminModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 1.5rem; overflow: hidden; box-shadow: 0 20px 60px rgba(15,23,42,0.15);">
            <div style="padding: 2rem; text-align: center; background: #ffffff;">

                <div style="width: 5rem; height: 5rem; border-radius: 999px; background: #fef2f2; color: #dc2626;
                            display: flex; align-items: center; justify-content: center;
                            font-size: 2rem; margin: 0 auto 1.25rem auto;">
                    <i class="bi bi-person-x-fill"></i>
                </div>

                <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.35rem;
                           font-weight: 700; color: #0f172a; margin-bottom: 0.75rem;">
                    Delete Admin Account?
                </h3>

                <p style="font-size: 0.9rem; line-height: 1.7; color: #64748b; margin-bottom: 2rem;">
                    This action cannot be undone.
                    The selected admin account will be permanently deleted.
                </p>

                <form method="POST">
                    <input type="hidden" name="form_type" value="delete_admin">
                    <input type="hidden" name="admin_id" id="deleteAdminId" value="">

                    <div style="display: flex; gap: 0.75rem;">

                        <button type="button"
                                data-bs-dismiss="modal"
                                style="flex: 1; height: 3rem; border-radius: 0.9rem; font-size: 0.85rem;
                                       font-weight: 600; border: 1px solid #e2e8f0; background: #f8fafc;
                                       color: #475569; cursor: pointer; transition: all 0.2s ease;">
                            Cancel
                        </button>

                        <button type="submit"
                                style="flex: 1; height: 3rem; border-radius: 0.9rem; font-size: 0.85rem;
                                       font-weight: 600; border: none; background: #dc2626;
                                       color: white; cursor: pointer; transition: all 0.2s ease;">
                            Yes, Delete
                        </button>

                    </div>
                </form>

            </div>
        </div>
    </div>
</div>
<script>
function confirmDeleteAdmin(id) {
    document.getElementById('deleteAdminId').value = id;

    const modalEl = document.getElementById('deleteAdminModal');

    // Destroy existing instance if any, then create fresh
    const existingModal = bootstrap.Modal.getInstance(modalEl);
    if (existingModal) {
        existingModal.dispose();
    }

    const modal = new bootstrap.Modal(modalEl, {
        backdrop: true,
        keyboard: true
    });

    modal.show();
}
</script>
<?php render_footer(); ?>