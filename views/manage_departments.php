<?php
$page_title = 'Manage Departments';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../backend/admin/manage_admin_actions.php';

if (!is_super_admin()) {
    header('Location: dashboard.php');
    exit();
}

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

$editDepartment = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editDepartment = get_department_by_id((int)$_GET['id']);
    if (!$editDepartment) {
        header('Location: manage_departments.php');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formType = $_POST['form_type'] ?? '';

    if ($formType === 'create_department') {
        $department_name = trim($_POST['department_name'] ?? '');
        $department_code = trim($_POST['department_code'] ?? '');

        if ($department_name === '' || $department_code === '') {
            $error = 'Please fill in all required fields.';
        } elseif (!create_department($department_name, $department_code)) {
            $error = 'Unable to create department. Name or code may already exist.';
        } else {
            $_SESSION['success'] = 'Department created successfully.';
            log_activity("[super_admin] {$_SESSION['username']} created department {$department_name}");
            header('Location: manage_departments.php');
            exit();
        }
    }

    if ($formType === 'update_department') {
        $department_id = intval($_POST['department_id'] ?? 0);
        $department_name = trim($_POST['department_name'] ?? '');
        $department_code = trim($_POST['department_code'] ?? '');

        if ($department_id <= 0 || $department_name === '' || $department_code === '') {
            $error = 'Please fill in all required fields.';
        } elseif (!update_department($department_id, $department_name, $department_code)) {
            $error = 'Unable to update department. Name or code may already exist.';
        } else {
            $_SESSION['success'] = 'Department updated successfully.';
            log_activity("[super_admin] {$_SESSION['username']} updated department {$department_name}");
            header('Location: manage_departments.php');
            exit();
        }
    }

    if ($formType === 'delete_department') {
        $department_id = intval($_POST['department_id'] ?? 0);

        if ($department_id <= 0) {
            $error = 'Invalid department selected.';
        } elseif (!delete_department($department_id)) {
            $error = 'Unable to delete department.';
        } else {
            $_SESSION['success'] = 'Department deleted successfully.';
            log_activity("[super_admin] {$_SESSION['username']} deleted department ID {$department_id}");
            header('Location: manage_departments.php');
            exit();
        }
    }
}

$departments = get_departments();

render_header($page_title);
?>
<style>
    .department-wrap {
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

    .department-grid {
        display: grid;
        grid-template-columns: 380px 1fr;
        gap: 1.5rem;
        align-items: start;
    }

    .department-card {
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

    .form-control-custom {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 0.875rem 1rem;
        font-size: 0.875rem;
        background: #fff;
        color: #0f172a;
        transition: all 0.15s ease;
    }

    .form-control-custom:focus {
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

    .department-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 700px;
    }

    .department-table thead tr {
        background: #f8fafc;
        border-bottom: 1px solid #f1f5f9;
    }

    .department-table thead th {
        padding: 0.875rem 1.5rem;
        text-align: left;
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
        color: #94a3b8;
        white-space: nowrap;
    }

    .department-table tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background 0.15s ease;
    }

    .department-table tbody tr:hover {
        background: #f8fafc;
    }

    .department-table tbody td {
        padding: 1rem 1.5rem;
        font-size: 0.875rem;
        color: #334155;
        vertical-align: middle;
    }

    .department-info {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .department-avatar {
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.75rem;
        background: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 700;
        color: #2563eb;
        flex-shrink: 0;
        text-transform: uppercase;
    }

    .department-name {
        font-weight: 700;
        color: #0f172a;
    }

    .department-code {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 0.15rem;
    }

    .date-text {
        color: #94a3b8;
        font-size: 0.8125rem;
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

    @media (max-width: 1100px) {
        .department-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="department-wrap">

    <div class="page-header">
        <div>
            <h1>Manage Departments</h1>
            <p><?= date('l, F j, Y') ?> · Department management overview</p>
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

    <div class="department-grid">

        <div class="department-card">

            <div class="card-header-custom">
                <h2><?= $editDepartment ? 'Edit Department' : 'Create Department' ?></h2>
            </div>

            <div class="card-body-custom">

                <form method="POST">

                    <input type="hidden"
                           name="form_type"
                           value="<?= $editDepartment ? 'update_department' : 'create_department' ?>">

                    <?php if ($editDepartment): ?>
                        <input type="hidden"
                               name="department_id"
                               value="<?= $editDepartment['id'] ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label">Department Name</label>

                        <input type="text"
                               name="department_name"
                               class="form-control-custom"
                               value="<?= htmlspecialchars($editDepartment['department_name'] ?? '') ?>"
                               required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department Code</label>

                        <input type="text"
                               name="department_code"
                               class="form-control-custom"
                               value="<?= htmlspecialchars($editDepartment['department_code'] ?? '') ?>"
                               required>
                    </div>

                    <div class="button-group">

                        <button class="btn-custom btn-primary-custom">
                            <?= $editDepartment ? 'Update Department' : 'Create Department' ?>
                        </button>

                        <?php if ($editDepartment): ?>

                            <a href="manage_departments.php"
                               class="btn-custom btn-secondary-custom">
                                Cancel
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </div>

        </div>

        <div class="department-card">

            <div class="card-header-custom">
                <h2>Departments</h2>
            </div>

            <div class="table-wrap">

                <table class="department-table">

                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($departments as $department): ?>

                        <tr>

                            <td>

                                <div class="department-info">

                                    <div class="department-avatar">
                                        <?= strtoupper(substr($department['department_code'], 0, 2)) ?>
                                    </div>

                                    <div>

                                        <div class="department-name">
                                            <?= htmlspecialchars($department['department_name']) ?>
                                        </div>

                                        <div class="department-code">
                                            <?= htmlspecialchars($department['department_code']) ?>
                                        </div>

                                    </div>

                                </div>

                            </td>

                            <td>
                                <span class="date-text">
                                    <?= !empty($department['created_at'])
                                        ? date('M d, Y', strtotime($department['created_at']))
                                        : 'No date' ?>
                                </span>
                            </td>

                            <td>

                                <div class="action-buttons">

                                    <a href="manage_departments.php?action=edit&id=<?= $department['id'] ?>"
                                       class="btn-table btn-edit">
                                        Edit
                                    </a>

                                    <button type="button"
                                            class="btn-table btn-delete"
                                            onclick="confirmDeleteDepartment(<?= $department['id'] ?>)">
                                        Delete
                                    </button>

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
<!-- Delete Department Modal -->
<div class="modal fade" id="deleteDepartmentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 1.5rem; overflow: hidden; box-shadow: 0 20px 60px rgba(15,23,42,0.15);">
            <div style="padding: 2rem; text-align: center; background: #ffffff;">

                <div style="width: 5rem; height: 5rem; border-radius: 999px; background: #fef2f2; color: #dc2626;
                            display: flex; align-items: center; justify-content: center;
                            font-size: 2rem; margin: 0 auto 1.25rem auto;">
                    <i class="bi bi-trash3-fill"></i>
                </div>

                <h3 style="font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.35rem;
                           font-weight: 700; color: #0f172a; margin-bottom: 0.75rem;">
                    Delete Department?
                </h3>

                <p style="font-size: 0.9rem; line-height: 1.7; color: #64748b; margin-bottom: 2rem;">
                    This action cannot be undone.
                    The selected department will be permanently deleted.
                </p>

                <form method="POST">
                    <input type="hidden" name="form_type" value="delete_department">
                    <input type="hidden" name="department_id" id="deleteDepartmentId" value="">

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
function confirmDeleteDepartment(id) {
    document.getElementById('deleteDepartmentId').value = id;
    new bootstrap.Modal(document.getElementById('deleteDepartmentModal')).show();
}
</script>

<?php render_footer(); ?>