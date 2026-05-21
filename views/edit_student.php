<?php
$page_title = 'Edit Student';
require_once __DIR__ . '/layout.php';
render_header($page_title);

$student_id = $_GET['id'] ?? 0;
$errors = [];
$success = '';

if (!$student_id) {
    header('Location: students.php');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php');
    exit();
}

$departments = $pdo->query('SELECT id, department_name, department_code FROM departments ORDER BY department_name')->fetchAll();
$currentDepartmentId = $_SESSION['department_id'] ?? null;
$currentDepartment = null;
foreach ($departments as $dept) {
    if ($dept['id'] == $currentDepartmentId) {
        $currentDepartment = $dept;
        break;
    }
}

if (!is_super_admin() && $student['department_id'] != $currentDepartmentId) {
    $_SESSION['error'] = 'You are not authorized to edit this student.';
    header('Location: students.php');
    exit();
}

$selectedDepartmentId = $student['department_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id_val = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $rfid_uid = trim($_POST['rfid_uid'] ?? '');
    $status = $_POST['status'] ?? 'Active';
    $department_id = null;
    $course = '';
    $selectedDepartmentId = null;

    if (is_super_admin()) {
        $selectedDepartmentId = intval($_POST['department_id'] ?? 0);
        if ($selectedDepartmentId <= 0) {
            $errors[] = 'Department is required for student updates.';
        } else {
            foreach ($departments as $dept) {
                if ($dept['id'] == $selectedDepartmentId) {
                    $department_id = $selectedDepartmentId;
                    $course = $dept['department_name'];
                    break;
                }
            }
            if ($department_id === null) {
                $errors[] = 'Selected department is invalid.';
            }
        }
    } else {
        $department_id = $currentDepartmentId;
        $selectedDepartmentId = $currentDepartmentId;
        if ($currentDepartment) {
            $course = $currentDepartment['department_name'];
        } else {
            $errors[] = 'Your department is not configured.';
        }
    }

    if (empty($student_id_val)) $errors[] = 'Student ID is required';
    if (empty($first_name)) $errors[] = 'First name is required';
    if (empty($last_name)) $errors[] = 'Last name is required';
    if (empty($course)) $errors[] = 'Course is required';
    if (empty($year_level)) $errors[] = 'Year level is required';
    if (empty($section)) $errors[] = 'Section is required';
    if (empty($contact_number)) $errors[] = 'Contact number is required';
    if (empty($email)) $errors[] = 'Email is required';
    if (empty($address)) $errors[] = 'Address is required';
    if (empty($rfid_uid)) $errors[] = 'RFID UID is required';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (!empty($student_id_val)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
        $stmt->execute([$student_id_val, $student_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Student ID already exists';
        }
    }

    if (!empty($rfid_uid)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE rfid_uid = ? AND id != ?");
        $stmt->execute([$rfid_uid, $student_id]);
        if ($stmt->fetch()) {
            $errors[] = 'RFID UID already exists';
        }
    }

    $photo_path = $student['photo'];
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024;

        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = 'Invalid photo format. Only JPG, PNG, and GIF are allowed.';
        } elseif ($_FILES['photo']['size'] > $max_size) {
            $errors[] = 'Photo size too large. Maximum 5MB allowed.';
        } else {
            $upload_dir = '../uploads/';
            $file_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                if ($student['photo'] && file_exists($upload_dir . $student['photo'])) {
                    unlink($upload_dir . $student['photo']);
                }
                $photo_path = $file_name;
            } else {
                $errors[] = 'Failed to upload photo.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET student_id = ?, first_name = ?, last_name = ?, middle_name = ?, course = ?, year_level = ?, section = ?, contact_number = ?, email = ?, address = ?, rfid_uid = ?, photo = ?, status = ?, department_id = ? WHERE id = ?");
            $stmt->execute([$student_id_val, $first_name, $last_name, $middle_name, $course, $year_level, $section, $contact_number, $email, $address, $rfid_uid, $photo_path, $status, $department_id, $student_id]);

            log_activity("[" . ($_SESSION['role'] ?? 'unknown') . "] " . ($_SESSION['username'] ?? 'unknown') . " updated student: $student_id_val");

            $success = 'Student updated successfully!';
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<style>
    .page-wrap {
        padding: 1.5rem;
        font-family: 'Sora', sans-serif;
        background: #f8fafc;
        min-height: 100%;
    }

    /* Page header */
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

    .btn-back {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.5rem 1rem;
        border-radius: 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #475569;
        background: #fff;
        border: 1px solid #e2e8f0;
        text-decoration: none;
        transition: border-color 0.15s, color 0.15s;
    }

    .btn-back:hover {
        border-color: #94a3b8;
        color: #0f172a;
    }

    .btn-back svg {
        width: 14px;
        height: 14px;
        stroke: currentColor;
    }

    /* Alerts */
    .alert {
        border-radius: 0.875rem;
        padding: 0.875rem 1rem;
        margin-bottom: 1.5rem;
        display: flex;
        gap: 0.75rem;
        align-items: flex-start;
    }

    .alert-error {
        background: #fef2f2;
        border: 1px solid #fecaca;
    }

    .alert-success {
        background: #f0fdf4;
        border: 1px solid #bbf7d0;
    }

    .alert-icon {
        width: 1.25rem;
        height: 1.25rem;
        flex-shrink: 0;
        margin-top: 0.0625rem;
    }

    .alert-error   .alert-icon { stroke: #dc2626; }
    .alert-success .alert-icon { stroke: #16a34a; }

    .alert ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .alert-error li {
        font-size: 0.8125rem;
        font-weight: 500;
        color: #dc2626;
    }

    .alert-success p {
        font-size: 0.8125rem;
        font-weight: 500;
        color: #16a34a;
        margin: 0;
    }

    /* Card */
    .card {
        background: #fff;
        border-radius: 1.25rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
        margin-bottom: 1rem;
    }

    .card-header {
        padding: 1.125rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .card-header-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .card-header-icon svg {
        width: 1rem;
        height: 1rem;
    }

    .card-header-icon.blue  { background: #caf0f8; }
    .card-header-icon.blue  svg { stroke: #0369a1; }
    .card-header-icon.orange { background: #fff3e0; }
    .card-header-icon.orange svg { stroke: #fb8500; }
    .card-header-icon.slate { background: #f1f5f9; }
    .card-header-icon.slate svg { stroke: #475569; }
    .card-header-icon.green { background: #d1fae5; }
    .card-header-icon.green svg { stroke: #059669; }

    .card-header h2 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .card-body {
        padding: 1.5rem;
    }

    /* Form grid */
    .form-grid {
        display: grid;
        gap: 1.25rem;
    }

    .form-grid.cols-2 { grid-template-columns: 1fr 1fr; }
    .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }

    @media (max-width: 640px) {
        .form-grid.cols-2,
        .form-grid.cols-3 { grid-template-columns: 1fr; }
    }

    .form-group { display: flex; flex-direction: column; gap: 0.375rem; }

    label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
    }

    .required { color: #fb8500; margin-left: 2px; }

    .hint {
        font-size: 0.75rem;
        color: #94a3b8;
        margin-top: 0.25rem;
    }

    .input-wrap { position: relative; }

    .input-wrap svg {
        position: absolute;
        left: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        width: 15px;
        height: 15px;
        stroke: #94a3b8;
        pointer-events: none;
    }

    input[type="text"],
    input[type="tel"],
    input[type="email"],
    input[type="file"],
    select,
    textarea {
        width: 100%;
        padding: 0.6875rem 0.875rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        font-family: 'Sora', sans-serif;
        font-size: 0.875rem;
        color: #0f172a;
        background: #f8fafc;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        -webkit-appearance: none;
    }

    .has-icon input,
    .has-icon select {
        padding-left: 2.5rem;
    }

    input:focus,
    select:focus,
    textarea:focus {
        border-color: #fb8500;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(251,133,0,0.1);
    }

    input::placeholder,
    textarea::placeholder { color: #cbd5e1; }

    select { cursor: pointer; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 0.875rem center; background-size: 14px; padding-right: 2.5rem; }

    textarea { resize: vertical; min-height: 90px; }

    input[type="file"] {
        padding: 0.5rem 0.875rem;
        cursor: pointer;
        font-size: 0.8125rem;
    }

    input:disabled,
    select:disabled {
        background: #f1f5f9;
        color: #94a3b8;
        cursor: not-allowed;
    }

    /* Plain text field (department read-only) */
    .field-plain {
        padding: 0.6875rem 0.875rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #f1f5f9;
        font-size: 0.875rem;
        color: #475569;
        font-weight: 500;
    }

    /* Photo preview */
    .photo-preview {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem 1rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
    }

    .photo-preview img {
        width: 3.5rem;
        height: 3.5rem;
        border-radius: 0.75rem;
        object-fit: cover;
        border: 2px solid #e2e8f0;
    }

    .photo-preview-info p {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
        margin: 0 0 0.125rem;
    }

    .photo-preview-info span {
        font-size: 0.75rem;
        color: #94a3b8;
    }

    /* Status toggle */
    .status-options {
        display: flex;
        gap: 0.75rem;
    }

    .status-option {
        flex: 1;
        position: relative;
    }

    .status-option input[type="radio"] {
        position: absolute;
        opacity: 0;
        width: 0;
        height: 0;
    }

    .status-option label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.625rem 1rem;
        border-radius: 0.75rem;
        border: 1.5px solid #e2e8f0;
        background: #f8fafc;
        cursor: pointer;
        font-size: 0.8125rem;
        font-weight: 600;
        color: #94a3b8;
        transition: all 0.15s;
    }

    .status-option label .dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .status-option input[type="radio"]:checked + label {
        border-color: transparent;
        color: #fff;
    }

    .status-option.active input[type="radio"]:checked + label { background: #059669; }
    .status-option.inactive input[type="radio"]:checked + label { background: #94a3b8; }

    /* Form actions */
    .form-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-top: 1rem;
        border-top: 1px solid #f1f5f9;
        margin-top: 0.5rem;
    }

    .btn-submit {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.75rem;
        background: #fb8500;
        color: #fff;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9375rem;
        font-weight: 700;
        border: none;
        border-radius: 0.75rem;
        cursor: pointer;
        transition: background 0.15s, box-shadow 0.15s, transform 0.1s;
    }

    .btn-submit:hover {
        background: #e07600;
        box-shadow: 0 4px 12px rgba(251,133,0,0.3);
    }

    .btn-submit:active { transform: scale(0.98); }

    .btn-submit svg { width: 16px; height: 16px; stroke: #fff; }

    .btn-cancel {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.75rem 1.25rem;
        background: #fff;
        color: #475569;
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        text-decoration: none;
        cursor: pointer;
        transition: border-color 0.15s, color 0.15s;
    }

    .btn-cancel:hover { border-color: #94a3b8; color: #0f172a; }
</style>

<div class="page-wrap">

    <div class="page-header">
        <div>
            <h1>Edit Student</h1>
            <p>Update student information and RFID details</p>
        </div>
        <a href="students.php" class="btn-back">
            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Students
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <svg class="alert-icon" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <svg class="alert-icon" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon blue">
                    <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                </div>
                <h2>RFID Information</h2>
            </div>
            <div class="card-body">
                <div class="form-grid cols-2">
                    <div class="form-group">
                        <label for="rfid_uid">RFID UID <span class="required">*</span></label>
                        <div class="input-wrap has-icon">
                            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                            <input type="text" id="rfid_uid" name="rfid_uid"
                                value="<?= htmlspecialchars($student['rfid_uid']) ?>"
                                placeholder="Scan or enter RFID UID" required>
                        </div>
                        <span class="hint">Scan the RFID card or enter manually</span>
                    </div>
                    <div class="form-group">
                        <label for="student_id">Student ID <span class="required">*</span></label>
                        <div class="input-wrap has-icon">
                            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0"/></svg>
                            <input type="text" id="student_id" name="student_id"
                                value="<?= htmlspecialchars($student['student_id']) ?>"
                                placeholder="e.g. 2021-00123" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon orange">
                    <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <h2>Personal Information</h2>
            </div>
            <div class="card-body">
                <div class="form-grid cols-3" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <input type="text" id="first_name" name="first_name"
                            value="<?= htmlspecialchars($student['first_name']) ?>"
                            placeholder="First name" required>
                    </div>
                    <div class="form-group">
                        <label for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name"
                            value="<?= htmlspecialchars($student['middle_name']) ?>"
                            placeholder="Middle name (optional)">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <input type="text" id="last_name" name="last_name"
                            value="<?= htmlspecialchars($student['last_name']) ?>"
                            placeholder="Last name" required>
                    </div>
                </div>

                <div class="form-grid cols-2" style="margin-bottom: 1.25rem;">
                    <div class="form-group">
                        <label for="contact_number">Contact Number <span class="required">*</span></label>
                        <div class="input-wrap has-icon">
                            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <input type="tel" id="contact_number" name="contact_number"
                                value="<?= htmlspecialchars($student['contact_number']) ?>"
                                placeholder="e.g. 09xx xxx xxxx" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <div class="input-wrap has-icon">
                            <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <input type="email" id="email" name="email"
                                value="<?= htmlspecialchars($student['email']) ?>"
                                placeholder="student@email.com" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="address">Address <span class="required">*</span></label>
                    <textarea id="address" name="address" placeholder="Complete address" required><?= htmlspecialchars($student['address']) ?></textarea>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon slate">
                    <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
                </div>
                <h2>Academic Information</h2>
            </div>
            <div class="card-body">
                <div class="form-grid cols-3">
                    <div class="form-group">
                        <label for="department_id">Course / Department <span class="required">*</span></label>
                        <?php if (is_super_admin()): ?>
                            <select id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"
                                        <?= ($selectedDepartmentId == $dept['id'] || $student['department_id'] == $dept['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <div class="field-plain"><?= htmlspecialchars($currentDepartment['department_name'] ?? '—') ?></div>
                            <input type="hidden" name="department_id" value="<?= htmlspecialchars($currentDepartmentId) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="year_level">Year Level <span class="required">*</span></label>
                        <select id="year_level" name="year_level" required>
                            <option value="">Select Year</option>
                            <?php foreach (['1st Year','2nd Year','3rd Year','4th Year'] as $yr): ?>
                                <option value="<?= $yr ?>" <?= $student['year_level'] == $yr ? 'selected' : '' ?>><?= $yr ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="section">Section <span class="required">*</span></label>
                        <input type="text" id="section" name="section"
                            value="<?= htmlspecialchars($student['section']) ?>"
                            placeholder="e.g. A" required>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon green">
                    <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <h2>Student Photo</h2>
            </div>
            <div class="card-body">
                <?php if ($student['photo']): ?>
                    <div class="photo-preview" style="margin-bottom: 1.25rem;">
                        <img src="../uploads/<?= htmlspecialchars($student['photo']) ?>" alt="Current Photo">
                        <div class="photo-preview-info">
                            <p>Current Photo</p>
                            <span>Upload a new photo below to replace it</span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="photo">Upload New Photo</label>
                    <input type="file" id="photo" name="photo" accept="image/*">
                    <span class="hint">Leave empty to keep current photo · Max 5MB · JPG, PNG, GIF only</span>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-header-icon slate">
                    <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2>Account Status</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Status</label>
                    <div class="status-options">
                        <div class="status-option active">
                            <input type="radio" id="status_active" name="status" value="Active"
                                <?= $student['status'] == 'Active' ? 'checked' : '' ?>>
                            <label for="status_active">
                                <span class="dot"></span> Active
                            </label>
                        </div>
                        <div class="status-option inactive">
                            <input type="radio" id="status_inactive" name="status" value="Inactive"
                                <?= $student['status'] == 'Inactive' ? 'checked' : '' ?>>
                            <label for="status_inactive">
                                <span class="dot"></span> Inactive
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-submit">
                <svg fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Update Student
            </button>
            <a href="students.php" class="btn-cancel">Cancel</a>
        </div>

    </form>
</div>

<?php render_footer(); ?>