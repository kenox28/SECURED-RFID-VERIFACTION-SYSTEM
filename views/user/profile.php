<?php
$page_title = 'Profile';
require_once __DIR__ . '/layout.php';
render_student_header($page_title);

$student = get_logged_student();
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_photo'])) {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType     = $_FILES['photo']['type'];
            $fileSize     = $_FILES['photo']['size'];

            if (!in_array($fileType, $allowedTypes, true)) {
                $errors[] = 'Photo must be a JPG, PNG, or GIF file.';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $errors[] = 'Photo must be smaller than 5MB.';
            } else {
                $uploadDir  = __DIR__ . '/../../uploads/';
                $fileName   = uniqid() . '_' . basename($_FILES['photo']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $stmt = $pdo->prepare('UPDATE students SET photo = ? WHERE id = ?');
                    $stmt->execute([$fileName, $student['id']]);
                    $success          = 'Profile photo updated successfully.';
                    $student['photo'] = $fileName;
                } else {
                    $errors[] = 'Unable to upload the selected photo.';
                }
            }
        } else {
            $errors[] = 'Please choose a photo to upload.';
        }
    }

    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $errors[] = 'All password fields are required.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $stmt = $pdo->prepare('SELECT password FROM students WHERE id = ?');
            $stmt->execute([$student['id']]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($current, $row['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $passwordErrors = student_validate_password($new);
                if (!empty($passwordErrors)) {
                    $errors = array_merge($errors, $passwordErrors);
                } else {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $stmt   = $pdo->prepare('UPDATE students SET password = ? WHERE id = ?');
                    $stmt->execute([$hashed, $student['id']]);
                    $success = 'Password changed successfully.';
                }
            }
        }
    }
}

$photoUrl = !empty($student['photo']) ? '/uploads/' . $student['photo'] : null;
?>

<style>
    .profile-wrap {
        display: grid;
        grid-template-columns: 320px 1fr;
        gap: 1.5rem;
        align-items: start;
    }

    @media (max-width: 900px) {
        .profile-wrap {
            grid-template-columns: 1fr;
        }
    }

    /* Cards */
    .p-card {
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 1.25rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }

    .p-card-header {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .p-card-header h2 {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.9375rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0;
        letter-spacing: -0.01em;
    }

    .p-card-header p {
        font-size: 0.78rem;
        color: #94a3b8;
        margin: 0.2rem 0 0;
    }

    .p-card-body {
        padding: 1.5rem;
    }

    /* Alert */
    .alert-custom {
        border-radius: 0.875rem;
        padding: 0.875rem 1.125rem;
        font-size: 0.8125rem;
        font-weight: 500;
        margin-bottom: 1.25rem;
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

    /* Photo section */
    .photo-section {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .photo-ring {
        width: 110px;
        height: 110px;
        border-radius: 50%;
        border: 3px solid #fb8500;
        padding: 3px;
        margin-bottom: 1rem;
        flex-shrink: 0;
    }

    .photo-ring img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .photo-placeholder {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        color: #94a3b8;
    }

    .photo-student-name {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 1rem;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 0.2rem;
    }

    .photo-student-id {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 500;
        margin-bottom: 0.2rem;
    }

    .photo-course-badge {
        display: inline-block;
        background: #fff7ed;
        color: #ea580c;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        margin-top: 0.35rem;
    }

    /* Upload area */
    .upload-area {
        border: 1.5px dashed #e2e8f0;
        border-radius: 0.875rem;
        padding: 1rem;
        text-align: center;
        background: #f8fafc;
        transition: 0.15s ease;
        margin-bottom: 1rem;
        cursor: pointer;
    }

    .upload-area:hover {
        border-color: #fb8500;
        background: #fffaf5;
    }

    .upload-area i {
        font-size: 1.5rem;
        color: #94a3b8;
        display: block;
        margin-bottom: 0.4rem;
    }

    .upload-area p {
        font-size: 0.75rem;
        color: #64748b;
        margin: 0;
    }

    .upload-area input[type="file"] {
        margin-top: 0.75rem;
        font-size: 0.78rem;
        width: 100%;
    }

    /* Info rows */
    .info-list {
        display: flex;
        flex-direction: column;
        gap: 0;
    }

    .info-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        padding: 0.875rem 0;
        border-bottom: 1px solid #f1f5f9;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-size: 0.78rem;
        color: #94a3b8;
        font-weight: 600;
        white-space: nowrap;
    }

    .info-value {
        font-size: 0.875rem;
        color: #0f172a;
        font-weight: 600;
        text-align: right;
    }

    /* Password form */
    .password-divider {
        border: none;
        border-top: 1px solid #f1f5f9;
        margin: 1.5rem 0;
    }

    .section-title {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-size: 0.875rem;
        font-weight: 700;
        color: #0f172a;
        margin: 0 0 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        font-size: 0.78rem;
        font-weight: 600;
        color: #475569;
        margin-bottom: 0.45rem;
    }

    .form-input {
        width: 100%;
        border: 1px solid #e2e8f0;
        border-radius: 0.875rem;
        padding: 0.8rem 1rem;
        font-size: 0.875rem;
        color: #0f172a;
        background: #fff;
        outline: none;
        transition: 0.15s ease;
        font-family: 'Sora', sans-serif;
    }

    .form-input:focus {
        border-color: #fb8500;
        box-shadow: 0 0 0 3px rgba(251,133,0,0.08);
    }

    /* Buttons */
    .btn-orange {
        border: none;
        background: #fb8500;
        color: #fff;
        height: 2.75rem;
        padding: 0 1.25rem;
        border-radius: 0.875rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        transition: 0.15s ease;
        font-family: 'Sora', sans-serif;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-orange:hover {
        background: #ea7b00;
        transform: translateY(-1px);
    }
</style>

<div class="profile-wrap">

    <!-- LEFT: Photo + upload -->
    <div class="p-card">

        <div class="p-card-header">
            <h2>Profile Photo</h2>
            <p>Update your student profile picture</p>
        </div>

        <div class="p-card-body">

            <?php if (!empty($errors) && isset($_POST['update_photo'])): ?>
                <div class="alert-custom alert-danger-custom">
                    <?php foreach ($errors as $e): ?>
                        <div><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success) && isset($_POST['update_photo'])): ?>
                <div class="alert-custom alert-success-custom">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="photo-section">
                <div class="photo-ring">
                    <?php if ($photoUrl): ?>
                        <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Profile Photo">
                    <?php else: ?>
                        <div class="photo-placeholder">
                            <i class="bi bi-person"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="photo-student-name">
                    <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
                </div>

                <div class="photo-student-id">
                    <?= htmlspecialchars($student['student_id']) ?>
                </div>

                <span class="photo-course-badge">
                    <?= htmlspecialchars($student['course'] ?? 'N/A') ?>
                </span>
            </div>

            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="update_photo" value="1">

                <div class="upload-area">
                    <i class="bi bi-cloud-upload"></i>
                    <p>JPG, PNG or GIF — max 5MB</p>
                    <input type="file" name="photo" accept="image/png,image/jpeg,image/gif">
                </div>

                <button type="submit" class="btn-orange">
                    <i class="bi bi-upload"></i>
                    Upload Photo
                </button>
            </form>

        </div>
    </div>

    <!-- RIGHT: Info + password -->
    <div class="p-card">

        <div class="p-card-header">
            <h2>Account Details</h2>
            <p>Your personal information and security settings</p>
        </div>

        <div class="p-card-body">

            <?php if (!empty($errors) && isset($_POST['change_password'])): ?>
                <div class="alert-custom alert-danger-custom">
                    <?php foreach ($errors as $e): ?>
                        <div><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success) && isset($_POST['change_password'])): ?>
                <div class="alert-custom alert-success-custom">
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <div class="info-list">
                <div class="info-row">
                    <span class="info-label">Student ID</span>
                    <span class="info-value"><?= htmlspecialchars($student['student_id']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?= htmlspecialchars($student['email']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Course</span>
                    <span class="info-value"><?= htmlspecialchars($student['course']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Year Level</span>
                    <span class="info-value"><?= htmlspecialchars($student['year_level']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Section</span>
                    <span class="info-value"><?= htmlspecialchars($student['section']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Contact</span>
                    <span class="info-value"><?= htmlspecialchars($student['contact_number']) ?></span>
                </div>
            </div>

            <hr class="password-divider">

            <p class="section-title">Change Password</p>

            <form method="POST">
                <input type="hidden" name="change_password" value="1">

                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>

                <button type="submit" class="btn-orange">
                    <i class="bi bi-shield-lock"></i>
                    Update Password
                </button>
            </form>

        </div>
    </div>

</div>

<?php render_student_footer(); ?>