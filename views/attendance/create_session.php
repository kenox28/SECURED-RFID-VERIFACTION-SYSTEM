<?php
ob_start();
require_once __DIR__ . '/../layout.php';
$page_title = 'Create Attendance Session';
render_header($page_title);

$errors = [];
$old = [];
$departments = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name ASC")->fetchAll();
$currentDepartmentId = $_SESSION['department_id'] ?? null;
$currentDepartmentName = 'General';
foreach ($departments as $department) {
    if ($department['id'] === $currentDepartmentId) {
        $currentDepartmentName = $department['department_name'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = $_POST;
    $session_name = trim($_POST['session_name'] ?? '');
    $attendance_type = trim($_POST['attendance_type'] ?? '');
    $start_time = trim($_POST['start_time'] ?? '');
    $end_time = trim($_POST['end_time'] ?? '');

    if ($session_name === '') $errors[] = 'Session name is required.';
    if (!in_array($attendance_type, ['IN', 'OUT'])) $errors[] = 'Invalid attendance type.';
    if ($start_time === '') $errors[] = 'Start time is required.';
    if ($end_time === '') $errors[] = 'End time is required.';
    if ($start_time && $end_time && $start_time >= $end_time) $errors[] = 'End time must be after start time.';

    $department_id = null;
    if (is_super_admin()) {
        $department_id = intval($_POST['department_id'] ?? 0) ?: null;
        if ($department_id !== null) {
            $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM departments WHERE id = ?');
            $existsStmt->execute([$department_id]);
            if ($existsStmt->fetchColumn() == 0) {
                $errors[] = 'Selected department does not exist.';
            }
        }
    } else {
        $department_id = $currentDepartmentId;
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO attendance_sessions (department_id, session_name, attendance_type, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, 'INACTIVE')");
            $stmt->execute([$department_id, $session_name, $attendance_type, $start_time, $end_time]);
            $_SESSION['flash'] = "Session \"$session_name\" created successfully.";
            header('Location: attendance_sessions.php');
            ob_end_flush();
            exit;
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<div class="container" style="max-width:600px">
    <div class="d-flex align-items-center gap-2 mt-4 mb-3">
        <a href="attendance_sessions.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h2 class="fw-bold mb-0">Create Attendance Session</h2>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Session Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="session_name" value="<?php echo htmlspecialchars($old['session_name'] ?? ''); ?>" placeholder="e.g. Morning Class — June 10, 2025" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Attendance Type <span class="text-danger">*</span></label>
                    <select class="form-select" name="attendance_type" required>
                        <option value="">— Select —</option>
                        <option value="IN" <?php echo (($old['attendance_type'] ?? '') === 'IN') ? 'selected' : ''; ?>>IN (Time-in)</option>
                        <option value="OUT" <?php echo (($old['attendance_type'] ?? '') === 'OUT') ? 'selected' : ''; ?>>OUT (Time-out)</option>
                    </select>
                </div>
                <?php if (is_super_admin()): ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <select class="form-select" name="department_id">
                            <option value="0">General / All Departments</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo $department['id']; ?>" <?php echo intval($old['department_id'] ?? 0) === $department['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($department['department_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Leave as General for sessions that apply to all departments.</div>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Department</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($currentDepartmentName); ?>" disabled>
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">Start Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="start_time" value="<?php echo htmlspecialchars($old['start_time'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-semibold">End Time <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="end_time" value="<?php echo htmlspecialchars($old['end_time'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="d-flex gap-2 mt-1">
                    <button type="submit" class="btn btn-primary px-4"><i class="bi bi-plus-circle me-1"></i> Create Session</button>
                    <a href="attendance_sessions.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php render_footer(); ?>