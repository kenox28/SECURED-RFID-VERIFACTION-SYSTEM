<?php
$page_title = 'Register Student';

require_once __DIR__ . '/layout.php';

render_header($page_title);

$errors = [];
$success = '';

define('STATUS_ACTIVE', 'Active');
define('STATUS_INACTIVE', 'Inactive');

$departments = $pdo->query("
    SELECT id, department_name, department_code
    FROM departments
    ORDER BY department_name
")->fetchAll(PDO::FETCH_ASSOC);

$currentDepartmentId = isset($_SESSION['department_id'])
    ? (int)$_SESSION['department_id']
    : null;

$currentDepartment = null;

foreach ($departments as $dept) {
    if ((int)$dept['id'] === $currentDepartmentId) {
        $currentDepartment = $dept;
        break;
    }
}

$selectedDepartmentId = null;

if (!is_super_admin()) {
    $selectedDepartmentId = $currentDepartmentId;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $student_id     = trim($_POST['student_id'] ?? '');
    $first_name     = trim($_POST['first_name'] ?? '');
    $last_name      = trim($_POST['last_name'] ?? '');
    $middle_name    = trim($_POST['middle_name'] ?? '');
    $year_level     = trim($_POST['year_level'] ?? '');
    $section        = trim($_POST['section'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $rfid_uid       = trim($_POST['rfid_uid'] ?? '');
    $status         = $_POST['status'] ?? STATUS_ACTIVE;

    $department_id      = null;
    $course             = '';
    $selectedDepartmentId = null;

    if (is_super_admin()) {

        $selectedDepartmentId = intval($_POST['department_id'] ?? 0);

        if ($selectedDepartmentId <= 0) {
            $errors[] = 'Department is required for student registration.';
        } else {
            foreach ($departments as $dept) {
                if ((int)$dept['id'] === $selectedDepartmentId) {
                    $department_id = $selectedDepartmentId;
                    $course        = $dept['department_name'];
                    break;
                }
            }
            if ($department_id === null) {
                $errors[] = 'Selected department is invalid.';
            }
        }

    } else {

        $department_id = $currentDepartmentId;
        $course        = $currentDepartment['department_name'] ?? '';

    }

    if (empty($student_id))     $errors[] = 'Student ID is required';
    if (empty($first_name))     $errors[] = 'First name is required';
    if (empty($last_name))      $errors[] = 'Last name is required';
    if (empty($course))         $errors[] = 'Course is required';
    if (empty($year_level))     $errors[] = 'Year level is required';
    if (empty($section))        $errors[] = 'Section is required';
    if (empty($contact_number)) $errors[] = 'Contact number is required';
    if (empty($email))          $errors[] = 'Email is required';
    if (empty($address))        $errors[] = 'Address is required';
    if (empty($rfid_uid))       $errors[] = 'RFID UID is required';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    if (!empty($student_id)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Student ID already exists';
        }
    }

    if (!empty($rfid_uid)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE rfid_uid = ?");
        $stmt->execute([$rfid_uid]);
        if ($stmt->fetch()) {
            $errors[] = 'RFID UID already exists';
        }
    }

    $photo_path = null;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {

        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size      = 5 * 1024 * 1024;

        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = 'Invalid photo format. Only JPG, PNG, and GIF are allowed.';
        } elseif ($_FILES['photo']['size'] > $max_size) {
            $errors[] = 'Photo size too large. Maximum 5MB allowed.';
        } else {
            $upload_dir  = '../uploads/';
            $file_name   = uniqid() . '_' . basename($_FILES['photo']['name']);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                $photo_path = $file_name;
            } else {
                $errors[] = 'Failed to upload photo.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO students
                (student_id, first_name, last_name, middle_name, course,
                 year_level, section, contact_number, email, address,
                 rfid_uid, photo, status, department_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $student_id, $first_name, $last_name, $middle_name,
                $course, $year_level, $section, $contact_number,
                $email, $address, $rfid_uid, $photo_path, $status, $department_id
            ]);

            log_activity(
                "[" . ($_SESSION['role'] ?? 'unknown') . "] " .
                ($_SESSION['username'] ?? 'unknown') .
                " registered student: $student_id"
            );

            $success = 'Student registered successfully!';

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<style>
    .register-wrap {
        padding: 1.5rem;
        font-family: 'Sora', sans-serif;
    }

    .page-header-custom {
        margin-bottom: 1.5rem;
    }

    .page-header-custom h1 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.02em;
    }

    .page-header-custom p {
        margin-top: 0.35rem;
        font-size: 0.8125rem;
        color: #94a3b8;
    }

    .register-card {
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .register-card-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .register-card-header h2 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
    }

    .register-card-header span {
        font-size: 0.75rem;
        font-weight: 600;
        color: #94a3b8;
    }

    .register-card-body {
        padding: 1.5rem;
    }

    .alert-box {
        border-radius: 1rem;
        padding: 1rem 1.25rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
        border: 1px solid;
    }

    .alert-danger-custom {
        background: #fef2f2;
        border-color: #fecaca;
        color: #b91c1c;
    }

    .alert-success-custom {
        background: #ecfdf5;
        border-color: #d1fae5;
        color: #047857;
    }

    .alert-box ul {
        margin: 0;
        padding-left: 1.2rem;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(12, 1fr);
        gap: 1.25rem;
    }

    .col-12 { grid-column: span 12; }
    .col-6  { grid-column: span 6; }
    .col-4  { grid-column: span 4; }

    @media (max-width: 768px) {
        .col-6,
        .col-4 { grid-column: span 12; }
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
    }

    .form-label {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #334155;
    }

    .form-input,
    .form-select,
    .form-textarea {
        width: 100%;
        border: 1px solid #e2e8f0;
        background: #fff;
        border-radius: 0.95rem;
        padding: 0.85rem 1rem;
        font-size: 0.875rem;
        color: #0f172a;
        transition: 0.15s ease;
        outline: none;
        font-family: 'Sora', sans-serif;
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .form-input:focus,
    .form-select:focus,
    .form-textarea:focus {
        border-color: #fb8500;
        box-shadow: 0 0 0 4px rgba(251,133,0,0.08);
    }

    .form-select:disabled {
        background: #f8fafc;
        color: #64748b;
        cursor: not-allowed;
        opacity: 1;
    }

    .upload-box {
        border: 2px dashed #e2e8f0;
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        background: #f8fafc;
        transition: 0.15s ease;
    }

    .upload-box:hover {
        border-color: #fb8500;
        background: #fffaf5;
    }

    .upload-box i {
        font-size: 2rem;
        color: #94a3b8;
        margin-bottom: 0.75rem;
        display: block;
    }

    .upload-box p {
        margin: 0;
        font-size: 0.8125rem;
        color: #64748b;
    }

    .submit-btn {
        border: none;
        background: #fb8500;
        color: #fff;
        height: 3rem;
        padding: 0 1.5rem;
        border-radius: 0.95rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.15s ease;
        margin-top: 1rem;
        font-family: 'Sora', sans-serif;
    }

    .submit-btn:hover {
        background: #ea7b00;
        transform: translateY(-1px);
    }
</style>

<div class="register-wrap">

    <div class="page-header-custom">
        <h1>Register Student</h1>
        <p>Create and register a new RFID student profile</p>
    </div>

    <div class="register-card">

        <div class="register-card-header">
            <div>
                <h2>Student Information</h2>
                <span>Fill in all required student details</span>
            </div>
        </div>

        <div class="register-card-body">

            <?php if (!empty($errors)): ?>
                <div class="alert-box alert-danger-custom">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="alert-box alert-success-custom">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">

                <div class="form-grid">

                    <!-- RFID UID -->
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">RFID UID *</label>
                            <input
                                type="text"
                                name="rfid_uid"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['rfid_uid'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- STUDENT ID -->
                    <div class="col-6">
                        <div class="form-group">
                            <label class="form-label">Student ID *</label>
                            <input
                                type="text"
                                name="student_id"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['student_id'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- FIRST NAME -->
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">First Name *</label>
                            <input
                                type="text"
                                name="first_name"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- MIDDLE NAME -->
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Middle Name</label>
                            <input
                                type="text"
                                name="middle_name"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>"
                            >
                        </div>
                    </div>

                    <!-- LAST NAME -->
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Last Name *</label>
                            <input
                                type="text"
                                name="last_name"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- COURSE / DEPARTMENT -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Course *</label>

                            <?php if (is_super_admin()): ?>

                                <select name="department_id" class="form-select" required>
                                    <option value="">Select Course</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option
                                            value="<?= $dept['id'] ?>"
                                            <?= ($selectedDepartmentId == $dept['id']) ? 'selected' : '' ?>
                                        >
                                            <?= htmlspecialchars($dept['department_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                            <?php else: ?>

                                <!-- Visible locked dropdown for dept admin -->
                                <select class="form-select" disabled>
                                    <?php if ($currentDepartment): ?>
                                        <option selected>
                                            <?= htmlspecialchars($currentDepartment['department_name']) ?>
                                        </option>
                                    <?php else: ?>
                                        <option>No department assigned</option>
                                    <?php endif; ?>
                                </select>

                                <!-- Hidden input that actually submits the value -->
                                <input
                                    type="hidden"
                                    name="department_id"
                                    value="<?= htmlspecialchars((string)$currentDepartmentId) ?>"
                                >

                            <?php endif; ?>

                        </div>
                    </div>

                    <!-- YEAR LEVEL -->
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Year Level *</label>
                            <select name="year_level" class="form-select" required>
                                <option value="">Select Year</option>
                                <option value="1st Year" <?= (($_POST['year_level'] ?? '') == '1st Year') ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= (($_POST['year_level'] ?? '') == '2nd Year') ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= (($_POST['year_level'] ?? '') == '3rd Year') ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= (($_POST['year_level'] ?? '') == '4th Year') ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <!-- SECTION -->
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Section *</label>
                            <input
                                type="text"
                                name="section"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['section'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- CONTACT -->
                    <div class="col-4">
                        <div class="form-group">
                            <label class="form-label">Contact Number *</label>
                            <input
                                type="text"
                                name="contact_number"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['contact_number'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- EMAIL -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Email *</label>
                            <input
                                type="email"
                                name="email"
                                class="form-input"
                                value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- ADDRESS -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Address *</label>
                            <textarea
                                name="address"
                                class="form-textarea"
                                required
                            ><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- PHOTO -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Student Photo</label>
                            <div class="upload-box">
                                <i class="bi bi-cloud-upload"></i>
                                <p>Upload JPG, PNG, or GIF (Max 5MB)</p>
                                <input
                                    type="file"
                                    name="photo"
                                    accept="image/jpeg,image/png,image/gif"
                                    style="margin-top: 1rem;"
                                >
                            </div>
                        </div>
                    </div>

                    <!-- STATUS -->
                    <div class="col-12">
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active"   <?= (($_POST['status'] ?? 'Active') == 'Active')   ? 'selected' : '' ?>>Active</option>
                                <option value="Inactive" <?= (($_POST['status'] ?? 'Active') == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                </div>

                <button type="submit" class="submit-btn">
                    Register Student
                </button>

            </form>

        </div>

    </div>

</div>

<?php render_footer(); ?>