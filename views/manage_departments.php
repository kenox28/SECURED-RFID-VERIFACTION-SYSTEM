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
                <h5><?= $editDepartment ? 'Edit Department' : 'Create Department' ?></h5>
            </div>

            <div class="card-body">
                <form method="POST">

                    <input type="hidden" name="form_type"
                           value="<?= $editDepartment ? 'update_department' : 'create_department' ?>">

                    <?php if ($editDepartment): ?>
                        <input type="hidden" name="department_id" value="<?= $editDepartment['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label>Department Name</label>
                        <input type="text" name="department_name" class="form-control"
                               value="<?= htmlspecialchars($editDepartment['department_name'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label>Department Code</label>
                        <input type="text" name="department_code" class="form-control"
                               value="<?= htmlspecialchars($editDepartment['department_code'] ?? '') ?>" required>
                    </div>

                    <button class="btn btn-primary">
                        <?= $editDepartment ? 'Update' : 'Create' ?>
                    </button>

                    <?php if ($editDepartment): ?>
                        <a href="manage_departments.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>

                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">
                <h5>Departments</h5>
            </div>

            <div class="card-body table-responsive">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($departments as $department): ?>
                        <tr>
                            <td><?= htmlspecialchars($department['department_name']) ?></td>
                            <td><?= htmlspecialchars($department['department_code']) ?></td>

                            <td>
                                <?= !empty($department['created_at'])
                                    ? date('M d, Y', strtotime($department['created_at']))
                                    : 'No date' ?>
                            </td>

                            <td>
                                <a href="manage_departments.php?action=edit&id=<?= $department['id'] ?>"
                                   class="btn btn-sm btn-warning">Edit</a>

                                <button type="button"
                                        class="btn btn-sm btn-danger"
                                        onclick="confirmDeleteDepartment(<?= $department['id'] ?>)">
                                    Delete
                                </button>
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
function confirmDeleteDepartment(id) {
    document.getElementById('deleteDepartmentId').value = id;
    new bootstrap.Modal(document.getElementById('deleteDepartmentModal')).show();
}
</script>

<?php render_footer(); ?>