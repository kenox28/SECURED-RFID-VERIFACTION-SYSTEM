<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../backend/admin/manage_admin_actions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_super_admin()) {
    if (isset($_POST['form_type']) && $_POST['form_type'] === 'create_admin') {
        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;

        if ($username === '' || $fullname === '' || $password === '' || $confirm_password === '') {
            $_SESSION['error'] = 'Please fill in all required admin fields.';
        } elseif ($password !== $confirm_password) {
            $_SESSION['error'] = 'Password and confirmation do not match.';
        } elseif (strlen($password) < 6) {
            $_SESSION['error'] = 'Password must be at least 6 characters long.';
        } elseif ($role !== 'super_admin' && $role !== 'admin') {
            $_SESSION['error'] = 'Invalid role selected.';
        } elseif ($role === 'admin' && (!$department_id || $department_id <= 0)) {
            $_SESSION['error'] = 'Please select a department for department admins.';
        } elseif (!create_admin($username, $fullname, $password, $role, $department_id)) {
            $_SESSION['error'] = 'Unable to create admin. Username may already exist.';
        } else {
            $_SESSION['success'] = 'Admin account created successfully.';
            log_activity("[super_admin] {$_SESSION['username']} created admin {$username}");
        }

        header('Location: dashboard.php#manage-admins');
        exit();
    }

    if (isset($_POST['form_type']) && $_POST['form_type'] === 'create_department') {
        $department_name = trim($_POST['department_name'] ?? '');
        $department_code = trim($_POST['department_code'] ?? '');

        if ($department_name === '' || $department_code === '') {
            $_SESSION['error'] = 'Please enter both department name and code.';
        } elseif (!create_department($department_name, $department_code)) {
            $_SESSION['error'] = 'Unable to create department. Name or code may already exist.';
        } else {
            $_SESSION['success'] = 'Department created successfully.';
            log_activity("[super_admin] {$_SESSION['username']} created department {$department_name}");
        }

        header('Location: dashboard.php#manage-departments');
        exit();
    }
}

render_header($page_title);

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students");
$total_students = $stmt->fetch()['total_students'];

// Total RFID cards (same as total students since each has one)
$total_rfid = $total_students;

// Recently registered students (last 5)
$stmt = $pdo->query("SELECT student_id, first_name, last_name, created_at FROM students ORDER BY created_at DESC LIMIT 5");
$recent_students = $stmt->fetchAll();

$admins = [];
$departments = [];
$activity_logs = [];
if (is_super_admin()) {
    $admins = get_admins();
    $departments = get_departments();
    $activity_logs = get_activity_logs(50);
}
?>
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">Total Students</h5>
                        <h2><?php echo $total_students; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-people display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="card-title">RFID Cards Registered</h5>
                        <h2><?php echo $total_rfid; ?></h2>
                    </div>
                    <div>
                        <i class="bi bi-credit-card display-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Recently Registered Students</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_students)): ?>
                    <p class="text-muted">No students registered yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Registered Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_students as $student): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($student['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (is_super_admin()): ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card" id="manage-admins">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Manage Admins</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <h6>Create New Admin</h6>
                            <form method="POST">
                                <input type="hidden" name="form_type" value="create_admin">
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" name="username" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" name="fullname" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Password</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <select class="form-select" name="role" required onchange="toggleDepartmentField(this.value)">
                                        <option value="admin">Department Admin</option>
                                        <option value="super_admin">Super Admin</option>
                                    </select>
                                </div>
                                <div class="mb-3" id="departmentSelectGroup">
                                    <label class="form-label">Department</label>
                                    <select class="form-select" name="department_id">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">Create Admin</button>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <h6>Admin List</h6>
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
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($admins)): ?>
                                            <tr><td colspan="6" class="text-muted">No admins found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($admin['fullname']); ?></td>
                                                    <td><?php echo htmlspecialchars($admin['role']); ?></td>
                                                    <td><?php echo htmlspecialchars($admin['department_name'] ?: 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($admin['status']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
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
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card" id="manage-departments">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5>Departments</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6 mb-4">
                            <h6>Create Department</h6>
                            <form method="POST">
                                <input type="hidden" name="form_type" value="create_department">
                                <div class="mb-3">
                                    <label class="form-label">Department Name</label>
                                    <input type="text" class="form-control" name="department_name" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Department Code</label>
                                    <input type="text" class="form-control" name="department_code" required>
                                </div>
                                <button type="submit" class="btn btn-primary">Create Department</button>
                            </form>
                        </div>
                        <div class="col-lg-6">
                            <h6>Department List</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($departments)): ?>
                                            <tr><td colspan="3" class="text-muted">No departments available.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($departments as $department): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($department['department_code']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($department['created_at'])); ?></td>
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
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card" id="activity-logs">
                <div class="card-header d-flex justify-content-between align-items-center">
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
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($activity_logs)): ?>
                                    <tr><td colspan="4" class="text-muted">No activity logs available.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($activity_logs as $log): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($log['username'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($log['role'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($log['activity']); ?></td>
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
<?php endif; ?>

<script>
function toggleDepartmentField(role) {
    document.getElementById('departmentSelectGroup').style.display = role === 'admin' ? 'block' : 'none';
}
window.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.querySelector('select[name="role"]');
    if (roleSelect) {
        toggleDepartmentField(roleSelect.value);
    }
});
</script>

<?php render_footer(); ?>