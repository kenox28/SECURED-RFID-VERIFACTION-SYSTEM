<?php
$page_title = 'Dashboard';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../backend/admin/manage_admin_actions.php';

/* =========================
   SAFE DATE FUNCTION
   (NO ERRORS EVER)
========================= */
function safe_date($value, $format = 'M d, Y H:i')
{
    if (!empty($value) && strtotime($value)) {
        return date($format, strtotime($value));
    }
    return 'No date';
}

/* =========================
   FORM HANDLING
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_super_admin()) {

    if ($_POST['form_type'] === 'create_admin') {

        $username = trim($_POST['username'] ?? '');
        $fullname = trim($_POST['fullname'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? 'admin';
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

        if ($password !== $confirm_password) {
            $_SESSION['error'] = 'Passwords do not match.';
        } elseif (!create_admin($username, $fullname, $password, $role, $department_id)) {
            $_SESSION['error'] = 'Failed to create admin.';
        } else {
            $_SESSION['success'] = 'Admin created successfully.';
        }

        header('Location: dashboard.php#manage-admins');
        exit();
    }

    if ($_POST['form_type'] === 'create_department') {

        $department_name = trim($_POST['department_name'] ?? '');
        $department_code = trim($_POST['department_code'] ?? '');

        if (!create_department($department_name, $department_code)) {
            $_SESSION['error'] = 'Failed to create department.';
        } else {
            $_SESSION['success'] = 'Department created successfully.';
        }

        header('Location: dashboard.php#manage-departments');
        exit();
    }
}

render_header($page_title);

/* =========================
   TOTAL STUDENTS
========================= */
$total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$total_rfid = $total_students;

/* =========================
   RECENT STUDENTS (SAFE - NO created_at REQUIRED)
========================= */
$stmt = $pdo->query("
    SELECT student_id, first_name, last_name 
    FROM students 
    ORDER BY id DESC 
    LIMIT 5
");

$recent_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   SUPER ADMIN DATA
========================= */
$admins = [];
$departments = [];
$activity_logs = [];

if (is_super_admin()) {
    $admins = get_admins();
    $departments = get_departments();
    $activity_logs = get_activity_logs(50);
}
?>

<!-- =========================
     STATS
========================= -->
<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5>Total Students</h5>
                <h2><?= $total_students ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5>RFID Cards</h5>
                <h2><?= $total_rfid ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- =========================
     RECENT STUDENTS
     (NO DATE REQUIRED)
========================= -->
<div class="card">
    <div class="card-header">
        <h5>Recently Registered Students</h5>
    </div>

    <div class="card-body">
        <?php if (empty($recent_students)): ?>
            <p>No students found.</p>
        <?php else: ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($recent_students as $student): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['student_id']) ?></td>
                            <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>
        <?php endif; ?>
    </div>
</div>

<?php if (is_super_admin()): ?>

<!-- =========================
     ADMINS
========================= -->
<div class="card mt-4">
    <div class="card-header">
        <h5>Admins</h5>
    </div>

    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Department</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td><?= htmlspecialchars($admin['username'] ?? '') ?></td>
                        <td><?= htmlspecialchars($admin['fullname'] ?? '') ?></td>
                        <td><?= htmlspecialchars($admin['role'] ?? '') ?></td>
                        <td><?= htmlspecialchars($admin['department_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($admin['status'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =========================
     DEPARTMENTS (NO created_at USED)
========================= -->
<div class="card mt-4">
    <div class="card-header">
        <h5>Departments</h5>
    </div>

    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Code</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($departments as $dept): ?>
                    <tr>
                        <td><?= htmlspecialchars($dept['department_name']) ?></td>
                        <td><?= htmlspecialchars($dept['department_code']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- =========================
     ACTIVITY LOGS (SAFE)
========================= -->
<div class="card mt-4">
    <div class="card-header">
        <h5>Activity Logs</h5>
    </div>

    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Activity</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($activity_logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['username'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($log['role'] ?? 'Unknown') ?></td>
                        <td><?= htmlspecialchars($log['activity'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php render_footer(); ?>