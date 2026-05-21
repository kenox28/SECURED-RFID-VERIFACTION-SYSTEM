<?php
ob_start();
require_once __DIR__ . '/../layout.php';
$page_title = 'Create Attendance Session';
render_header($page_title);

$errors      = [];
$old         = [];
$departments = $pdo->query("SELECT id, department_name FROM departments ORDER BY department_name ASC")->fetchAll();

$currentDepartmentId   = $_SESSION['department_id'] ?? null;
$currentDepartmentName = 'General';
foreach ($departments as $department) {
    if ($department['id'] === $currentDepartmentId) {
        $currentDepartmentName = $department['department_name'];
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old            = $_POST;
    $session_name   = trim($_POST['session_name'] ?? '');
    $attendance_type = trim($_POST['attendance_type'] ?? '');
    $start_time     = trim($_POST['start_time'] ?? '');
    $end_time       = trim($_POST['end_time'] ?? '');

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
            if ($existsStmt->fetchColumn() == 0) $errors[] = 'Selected department does not exist.';
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

<style>
    .page-wrap { padding: 1.5rem; font-family: 'Sora', sans-serif; background: #f8fafc; min-height: 100%; }

    .page-header { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem; }
    .page-header h1 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 1.5rem; font-weight: 700; color: #0f172a; letter-spacing: -0.02em; margin: 0; }
    .page-header p  { font-size: 0.8125rem; color: #94a3b8; margin: 0.25rem 0 0; }

    .btn-back { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.5rem 1rem; border-radius: 0.75rem; font-size: 0.8125rem; font-weight: 600; color: #475569; background: #fff; border: 1px solid #e2e8f0; text-decoration: none; transition: border-color 0.15s, color 0.15s; }
    .btn-back:hover { border-color: #94a3b8; color: #0f172a; }
    .btn-back svg { width: 14px; height: 14px; stroke: currentColor; }

    /* Alert */
    .alert { border-radius: 0.875rem; padding: 0.875rem 1rem; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 0.75rem; }
    .alert-error { background: #fef2f2; border: 1px solid #fecaca; }
    .alert svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; margin-top: 0.0625rem; stroke: #dc2626; }
    .alert ul { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 0.25rem; }
    .alert li { font-size: 0.8125rem; font-weight: 500; color: #dc2626; }

    /* Card */
    .card { background: #fff; border-radius: 1.25rem; border: 1px solid #f1f5f9; box-shadow: 0 1px 3px rgba(0,0,0,0.04); overflow: hidden; max-width: 100%; }
    .card-header { padding: 1.125rem 1.5rem; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 0.75rem; }
    .card-header-icon { width: 2rem; height: 2rem; border-radius: 0.5rem; background: #fff3e0; display: flex; align-items: center; justify-content: center; }
    .card-header-icon svg { width: 1rem; height: 1rem; stroke: #fb8500; }
    .card-header h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9375rem; font-weight: 700; color: #0f172a; margin: 0; }
    .card-body { padding: 1.5rem; }

    /* Form */
    .form-group { display: flex; flex-direction: column; gap: 0.375rem; margin-bottom: 1.25rem; }
    .form-group:last-child { margin-bottom: 0; }

    label { font-size: 0.8125rem; font-weight: 600; color: #334155; }
    .required { color: #fb8500; margin-left: 2px; }
    .hint { font-size: 0.75rem; color: #94a3b8; margin-top: 0.25rem; }

    input[type="text"], input[type="time"], select {
        width: 100%; padding: 0.6875rem 0.875rem;
        border: 1px solid #e2e8f0; border-radius: 0.75rem;
        font-family: 'Sora', sans-serif; font-size: 0.875rem;
        color: #0f172a; background: #f8fafc; outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        -webkit-appearance: none;
    }
    input:focus, select:focus { border-color: #fb8500; background: #fff; box-shadow: 0 0 0 3px rgba(251,133,0,0.1); }
    input::placeholder { color: #cbd5e1; }
    select { background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.875rem center; background-size: 13px; padding-right: 2.25rem; cursor: pointer; }
    input:disabled, select:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }

    /* Attendance type toggle */
    .type-options { display: flex; gap: 0.75rem; }
    .type-option { flex: 1; position: relative; }
    .type-option input[type="radio"] { position: absolute; opacity: 0; width: 0; height: 0; }
    .type-option label { display: flex; flex-direction: column; align-items: center; gap: 0.375rem; padding: 0.875rem 1rem; border-radius: 0.875rem; border: 1.5px solid #e2e8f0; background: #f8fafc; cursor: pointer; font-size: 0.8125rem; font-weight: 600; color: #94a3b8; transition: all 0.15s; text-align: center; }
    .type-option label svg { width: 1.25rem; height: 1.25rem; stroke: currentColor; }
    .type-option label .type-label { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9375rem; font-weight: 700; }
    .type-option label .type-sub { font-size: 0.75rem; }
    .type-option input[type="radio"]:checked + label { border-color: #fb8500; background: #fff3e0; color: #fb8500; }

    /* Time grid */
    .time-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    @media (max-width: 480px) { .time-grid { grid-template-columns: 1fr; } }

    /* Form actions */
    .form-actions { display: flex; align-items: center; gap: 0.75rem; padding-top: 1rem; border-top: 1px solid #f1f5f9; margin-top: 1.5rem; }

    .btn-submit { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.75rem; background: #fb8500; color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.9375rem; font-weight: 700; border: none; border-radius: 0.75rem; cursor: pointer; transition: background 0.15s, box-shadow 0.15s, transform 0.1s; }
    .btn-submit:hover { background: #e07600; box-shadow: 0 4px 12px rgba(251,133,0,0.3); }
    .btn-submit:active { transform: scale(0.98); }
    .btn-submit svg { width: 16px; height: 16px; stroke: #fff; }

    .btn-cancel { display: inline-flex; align-items: center; gap: 0.375rem; padding: 0.75rem 1.25rem; background: #fff; color: #475569; font-family: 'Plus Jakarta Sans', sans-serif; font-size: 0.875rem; font-weight: 600; border: 1px solid #e2e8f0; border-radius: 0.75rem; text-decoration: none; transition: border-color 0.15s, color 0.15s; }
    .btn-cancel:hover { border-color: #94a3b8; color: #0f172a; }
</style>

<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1>Create Session</h1>
            <p>Set up a new attendance session for students</p>
        </div>
        <a href="attendance_sessions.php" class="btn-back">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Sessions
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error" style="max-width: 640px;">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="card-header-icon">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h2>Session Details</h2>
        </div>
        <div class="card-body">
            <form method="POST" novalidate>

                <div class="form-group">
                    <label for="session_name">Session Name <span class="required">*</span></label>
                    <input type="text" id="session_name" name="session_name"
                        value="<?= htmlspecialchars($old['session_name'] ?? '') ?>"
                        placeholder="e.g. Morning Class — June 10, 2025" required>
                </div>

                <div class="form-group">
                    <label>Attendance Type <span class="required">*</span></label>
                    <div class="type-options">
                        <div class="type-option">
                            <input type="radio" id="type_in" name="attendance_type" value="IN"
                                <?= (($old['attendance_type'] ?? '') === 'IN') ? 'checked' : '' ?>>
                            <label for="type_in">
                                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                                <span class="type-label">IN</span>
                                <span class="type-sub">Time-in / Entry</span>
                            </label>
                        </div>
                        <div class="type-option">
                            <input type="radio" id="type_out" name="attendance_type" value="OUT"
                                <?= (($old['attendance_type'] ?? '') === 'OUT') ? 'checked' : '' ?>>
                            <label for="type_out">
                                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                <span class="type-label">OUT</span>
                                <span class="type-sub">Time-out / Exit</span>
                            </label>
                        </div>
                    </div>
                </div>

                <?php if (is_super_admin()): ?>
                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" name="department_id">
                        <option value="0">General / All Departments</option>
                        <?php foreach ($departments as $department): ?>
                            <option value="<?= $department['id'] ?>"
                                <?= intval($old['department_id'] ?? 0) === $department['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($department['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="hint">Leave as General for sessions that apply to all departments.</span>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" value="<?= htmlspecialchars($currentDepartmentName) ?>" disabled>
                </div>
                <?php endif; ?>

                <div class="time-grid">
                    <div class="form-group" style="margin-bottom:0">
                        <label for="start_time">Start Time <span class="required">*</span></label>
                        <input type="time" id="start_time" name="start_time"
                            value="<?= htmlspecialchars($old['start_time'] ?? '') ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label for="end_time">End Time <span class="required">*</span></label>
                        <input type="time" id="end_time" name="end_time"
                            value="<?= htmlspecialchars($old['end_time'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-submit">
                        <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Create Session
                    </button>
                    <a href="attendance_sessions.php" class="btn-cancel">Cancel</a>
                </div>

            </form>
        </div>
    </div>

</div>

<?php render_footer(); ?>