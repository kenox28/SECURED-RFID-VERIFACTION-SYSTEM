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
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>RFID UID *</label>
                            <input type="text" name="rfid_uid" class="form-control" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Student ID *</label>
                            <input type="text" name="student_id" class="form-control" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>First Name *</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Middle Name</label>
                            <input type="text" name="middle_name" class="form-control">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Last Name *</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <!-- ONLY COURSE DROPDOWN -->
                    <div class="mb-3">
                        <label>Course *</label>

                        <?php if (is_super_admin()): ?>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Course</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"
                                        <?= ($selectedDepartmentId == $dept['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                        <?php else: ?>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($currentDepartment['department_name'] ?? '') ?>" readonly>
                            <input type="hidden" name="department_id" value="<?= $currentDepartmentId ?>">
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Year Level *</label>
                            <select name="year_level" class="form-select" required>
                                <option value="">Select Year</option>
                                <option>1st Year</option>
                                <option>2nd Year</option>
                                <option>3rd Year</option>
                                <option>4th Year</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Section *</label>
                            <input type="text" name="section" class="form-control" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Contact *</label>
                            <input type="text" name="contact_number" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label>Address *</label>
                        <textarea name="address" class="form-control" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label>Photo</label>
                        <input type="file" name="photo" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label>Status</label>
                        <select name="status" class="form-select">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <button class="btn btn-primary">Register Student</button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>