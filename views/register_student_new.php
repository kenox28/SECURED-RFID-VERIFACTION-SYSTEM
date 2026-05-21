<?php
$page_title = 'Register Student';
require_once __DIR__ . '/layout.php';
render_header($page_title);

$errors = [];
$success = '';

define('STATUS_ACTIVE', 'Active');
define('STATUS_INACTIVE', 'Inactive');

$departments = $pdo->query('SELECT id, department_name, department_code FROM departments ORDER BY department_name')->fetchAll();

$currentDepartmentId = $_SESSION['department_id'] ?? null;
$currentDepartment = null;

foreach ($departments as $dept) {
    if ($dept['id'] == $currentDepartmentId) {
        $currentDepartment = $dept;
        break;
    }
}

$selectedDepartmentId = null;
if (!is_super_admin()) {
    $selectedDepartmentId = $currentDepartmentId;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $rfid_uid = trim($_POST['rfid_uid'] ?? '');
    $status = $_POST['status'] ?? STATUS_ACTIVE;

    $department_id = null;
    $course = '';
    $selectedDepartmentId = null;

    if (is_super_admin()) {
        $selectedDepartmentId = intval($_POST['department_id'] ?? 0);

        if ($selectedDepartmentId <= 0) {
            $errors[] = 'Department is required for student registration.';
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
        $course = $currentDepartment['department_name'] ?? '';
    }

    if (empty($student_id)) $errors[] = 'Student ID is required';
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
                (student_id, first_name, last_name, middle_name, course, year_level, section, contact_number, email, address, rfid_uid, photo, status, department_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $student_id,
                $first_name,
                $last_name,
                $middle_name,
                $course,
                $year_level,
                $section,
                $contact_number,
                $email,
                $address,
                $rfid_uid,
                $photo_path,
                $status,
                $department_id
            ]);

            log_activity("[" . ($_SESSION['role'] ?? 'unknown') . "] " . ($_SESSION['username'] ?? 'unknown') . " registered student: $student_id");

            $success = 'Student registered successfully!';
            $student_id = $first_name = $last_name = $middle_name = $year_level = $section = $contact_number = $email = $address = $rfid_uid = '';

        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div style="max-width: 900px; margin: 0 auto;">
    <?php if (!empty($errors)): ?>
        <div class="card-container mb-4" style="background: rgba(239, 68, 68, 0.05); border-color: var(--color-error);">
            <div class="flex items-center gap-3 mb-2">
                <svg class="w-5 h-5" style="color: var(--color-error); flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <ul style="margin: 0; padding-left: 0;">
                    <?php foreach ($errors as $error): ?>
                        <li style="color: var(--color-error);"><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="card-container mb-4" style="background: rgba(16, 185, 129, 0.05); border-color: var(--color-success);">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5" style="color: var(--color-success); flex-shrink: 0;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span style="color: var(--color-success);"><?php echo htmlspecialchars($success); ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="card-container">
        <h3 class="text-lg-heading mb-6">Register New Student</h3>
        
        <form method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">RFID UID <span style="color: var(--color-error);">*</span></label>
                    <input type="text" name="rfid_uid" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['rfid_uid'] ?? ''); ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Student ID <span style="color: var(--color-error);">*</span></label>
                    <input type="text" name="student_id" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">First Name <span style="color: var(--color-error);">*</span></label>
                    <input type="text" name="first_name" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Middle Name</label>
                    <input type="text" name="middle_name" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['middle_name'] ?? ''); ?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Last Name <span style="color: var(--color-error);">*</span></label>
                    <input type="text" name="last_name" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Course <span style="color: var(--color-error);">*</span></label>
                <?php if (is_super_admin()): ?>
                    <select name="department_id" class="input-field" style="width: 100%;" required>
                        <option value="">Select Course</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['id'] ?>" <?= ($selectedDepartmentId == $dept['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dept['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <div class="input-field" style="width: 100%; background: var(--color-bg-secondary); cursor: not-allowed;">
                        <?= htmlspecialchars($currentDepartment['department_name'] ?? 'N/A') ?>
                    </div>
                    <input type="hidden" name="department_id" value="<?= $currentDepartmentId ?>">
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem;">
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Year Level <span style="color: var(--color-error);">*</span></label>
                    <select name="year_level" class="input-field" style="width: 100%;" required>
                        <option value="">Select Year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Section <span style="color: var(--color-error);">*</span></label>
                    <input type="text" name="section" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['section'] ?? ''); ?>" required>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Contact <span style="color: var(--color-error);">*</span></label>
                    <input type="tel" name="contact_number" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['contact_number'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Email <span style="color: var(--color-error);">*</span></label>
                <input type="email" name="email" class="input-field" style="width: 100%;" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Address <span style="color: var(--color-error);">*</span></label>
                <textarea name="address" class="input-field" style="width: 100%; resize: vertical; min-height: 80px;" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Photo</label>
                <div style="border: 2px dashed var(--color-border); border-radius: 0.75rem; padding: 1.5rem; text-align: center;">
                    <input type="file" name="photo" accept="image/*" style="width: 100%; cursor: pointer;">
                    <p class="text-xs mt-2" style="color: var(--color-text-secondary);">JPG, PNG, GIF (max 5MB)</p>
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Status</label>
                <select name="status" class="input-field" style="width: 100%;">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div style="display: flex; gap: 1rem;">
                <button type="submit" class="btn-primary">Register Student</button>
                <a href="students.php" class="btn-secondary" style="text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.5rem;">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php render_footer(); ?>
