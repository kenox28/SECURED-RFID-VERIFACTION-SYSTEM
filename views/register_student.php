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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $middle_name = trim($_POST['middle_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $rfid_uid = trim($_POST['rfid_uid'] ?? '');
    $status = $_POST['status'] ?? STATUS_ACTIVE;
    $department_id = null;

    if (is_super_admin()) {
        $department_id = intval($_POST['department_id'] ?? 0);
        if ($department_id <= 0) {
            $errors[] = 'Department is required for student registration.';
        }
    } else {
        $department_id = $currentDepartmentId;
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
            $stmt = $pdo->prepare("INSERT INTO students (student_id, first_name, last_name, middle_name, course, year_level, section, contact_number, email, address, rfid_uid, photo, status, department_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ");
            $stmt->execute([$student_id, $first_name, $last_name, $middle_name, $course, $year_level, $section, $contact_number, $email, $address, $rfid_uid, $photo_path, $status, $department_id]);

            log_activity("[" . ($_SESSION['role'] ?? 'unknown') . "] " . ($_SESSION['username'] ?? 'unknown') . " registered student: $student_id");

            $success = 'Student registered successfully!';
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5>Register New Student</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="rfid_uid" class="form-label">RFID UID *</label>
                            <input type="text" class="form-control" id="rfid_uid" name="rfid_uid" required autofocus>
                            <div class="form-text">Scan RFID card or enter manually</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="student_id" class="form-label">Student ID *</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="course" class="form-label">Course *</label>
                            <input type="text" class="form-control" id="course" name="course" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="year_level" class="form-label">Year Level *</label>
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="">Select Year</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="section" class="form-label">Section *</label>
                            <input type="text" class="form-control" id="section" name="section" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Contact Number *</label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="address" class="form-label">Address *</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                    </div>

                    <?php if (is_super_admin()): ?>
                        <div class="mb-3">
                            <label for="department_id" class="form-label">Department *</label>
                            <select class="form-select" id="department_id" name="department_id" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_POST['department_id']) && $_POST['department_id'] == $dept['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($dept['department_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php else: ?>
                        <?php if ($currentDepartment): ?>
                            <div class="mb-3">
                                <label class="form-label">Department</label>
                                <div class="form-control-plaintext"><?php echo htmlspecialchars($currentDepartment['department_name']); ?></div>
                            </div>
                            <input type="hidden" name="department_id" value="<?php echo htmlspecialchars($currentDepartmentId); ?>">
                        <?php endif; ?>
                    <?php endif; ?>

                    <div class="mb-3">
                        <label for="photo" class="form-label">Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <div class="form-text">Optional. Max 5MB. JPG, PNG, GIF only.</div>
                    </div>

                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Register Student</button>
                    <a href="students.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const rfidInput = document.getElementById('rfid_uid');
    rfidInput.focus();
    let rfidBuffer = '';
    let lastKeyTime = Date.now();

    document.addEventListener('keydown', function(e) {
        const currentTime = Date.now();
        if (currentTime - lastKeyTime > 100) {
            rfidBuffer = '';
        }
        lastKeyTime = currentTime;
        if (e.key.length === 1 && e.key.match(/[a-zA-Z0-9]/)) {
            rfidBuffer += e.key;
            if (rfidBuffer.length >= 8) {
                rfidInput.value = rfidBuffer;
                rfidBuffer = '';
                document.getElementById('student_id').focus();
            }
        }
    });
});
</script>

<?php render_footer(); ?>