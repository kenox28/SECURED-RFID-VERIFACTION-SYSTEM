<?php
// pages/edit_student.php
$page_title = 'Edit Student';
require_once '../includes/header.php';

$student_id = $_GET['id'] ?? 0;
$errors = [];
$success = '';

if (!$student_id) {
    header('Location: students.php');
    exit();
}

// Get student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header('Location: students.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $student_id_val = trim($_POST['student_id'] ?? '');
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
    $status = $_POST['status'] ?? 'Active';

    // Validation
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

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    }

    // Check for duplicate student_id (excluding current)
    if (!empty($student_id_val)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? AND id != ?");
        $stmt->execute([$student_id_val, $student_id]);
        if ($stmt->fetch()) {
            $errors[] = 'Student ID already exists';
        }
    }

    // Check for duplicate rfid_uid (excluding current)
    if (!empty($rfid_uid)) {
        $stmt = $pdo->prepare("SELECT id FROM students WHERE rfid_uid = ? AND id != ?");
        $stmt->execute([$rfid_uid, $student_id]);
        if ($stmt->fetch()) {
            $errors[] = 'RFID UID already exists';
        }
    }

    // Handle file upload
    $photo_path = $student['photo']; // Keep existing photo
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['photo']['type'], $allowed_types)) {
            $errors[] = 'Invalid photo format. Only JPG, PNG, and GIF are allowed.';
        } elseif ($_FILES['photo']['size'] > $max_size) {
            $errors[] = 'Photo size too large. Maximum 5MB allowed.';
        } else {
            $upload_dir = '../uploads/';
            $file_name = uniqid() . '_' . basename($_FILES['photo']['name']);
            $target_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_path)) {
                // Delete old photo if exists
                if ($student['photo'] && file_exists($upload_dir . $student['photo'])) {
                    unlink($upload_dir . $student['photo']);
                }
                $photo_path = $file_name;
            } else {
                $errors[] = 'Failed to upload photo.';
            }
        }
    }

    // If no errors, update database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE students SET student_id = ?, first_name = ?, last_name = ?, middle_name = ?, course = ?, year_level = ?, section = ?, contact_number = ?, email = ?, address = ?, rfid_uid = ?, photo = ?, status = ? WHERE id = ?");
            $stmt->execute([$student_id_val, $first_name, $last_name, $middle_name, $course, $year_level, $section, $contact_number, $email, $address, $rfid_uid, $photo_path, $status, $student_id]);

            // Log activity
            $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)");
            $stmt->execute([$_SESSION['admin_id'], "Updated student: $student_id_val"]);

            $success = 'Student updated successfully!';
            // Refresh student data
            $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
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
                <h5>Edit Student</h5>
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
                        <!-- RFID UID -->
                        <div class="col-md-6 mb-3">
                            <label for="rfid_uid" class="form-label">RFID UID *</label>
                            <input type="text" class="form-control" id="rfid_uid" name="rfid_uid" value="<?php echo htmlspecialchars($student['rfid_uid']); ?>" required>
                            <div class="form-text">Scan RFID card or enter manually</div>
                        </div>

                        <!-- Student ID -->
                        <div class="col-md-6 mb-3">
                            <label for="student_id" class="form-label">Student ID *</label>
                            <input type="text" class="form-control" id="student_id" name="student_id" value="<?php echo htmlspecialchars($student['student_id']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- First Name -->
                        <div class="col-md-4 mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>

                        <!-- Middle Name -->
                        <div class="col-md-4 mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($student['middle_name']); ?>">
                        </div>

                        <!-- Last Name -->
                        <div class="col-md-4 mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Course -->
                        <div class="col-md-4 mb-3">
                            <label for="course" class="form-label">Course *</label>
                            <input type="text" class="form-control" id="course" name="course" value="<?php echo htmlspecialchars($student['course']); ?>" required>
                        </div>

                        <!-- Year Level -->
                        <div class="col-md-4 mb-3">
                            <label for="year_level" class="form-label">Year Level *</label>
                            <select class="form-select" id="year_level" name="year_level" required>
                                <option value="">Select Year</option>
                                <option value="1st Year" <?php echo $student['year_level'] == '1st Year' ? 'selected' : ''; ?>>1st Year</option>
                                <option value="2nd Year" <?php echo $student['year_level'] == '2nd Year' ? 'selected' : ''; ?>>2nd Year</option>
                                <option value="3rd Year" <?php echo $student['year_level'] == '3rd Year' ? 'selected' : ''; ?>>3rd Year</option>
                                <option value="4th Year" <?php echo $student['year_level'] == '4th Year' ? 'selected' : ''; ?>>4th Year</option>
                            </select>
                        </div>

                        <!-- Section -->
                        <div class="col-md-4 mb-3">
                            <label for="section" class="form-label">Section *</label>
                            <input type="text" class="form-control" id="section" name="section" value="<?php echo htmlspecialchars($student['section']); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Contact Number -->
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Contact Number *</label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($student['contact_number']); ?>" required>
                        </div>

                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                        </div>
                    </div>

                    <!-- Address -->
                    <div class="mb-3">
                        <label for="address" class="form-label">Address *</label>
                        <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>

                    <!-- Current Photo -->
                    <?php if ($student['photo']): ?>
                        <div class="mb-3">
                            <label class="form-label">Current Photo</label>
                            <div>
                                <img src="../uploads/<?php echo htmlspecialchars($student['photo']); ?>" alt="Current Photo" class="img-thumbnail" width="150">
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Photo -->
                    <div class="mb-3">
                        <label for="photo" class="form-label">Update Photo</label>
                        <input type="file" class="form-control" id="photo" name="photo" accept="image/*">
                        <div class="form-text">Leave empty to keep current photo. Max 5MB. JPG, PNG, GIF only.</div>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="Active" <?php echo $student['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $student['status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Update Student</button>
                    <a href="students.php" class="btn btn-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>