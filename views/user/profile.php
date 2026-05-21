<?php
$page_title = 'Profile';
require_once __DIR__ . '/layout.php';
render_student_header($page_title);

$student = get_logged_student();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_photo'])) {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $fileType = $_FILES['photo']['type'];
            $fileSize = $_FILES['photo']['size'];

            if (!in_array($fileType, $allowedTypes, true)) {
                $errors[] = 'Photo must be a JPG, PNG, or GIF file.';
            } elseif ($fileSize > 5 * 1024 * 1024) {
                $errors[] = 'Photo must be smaller than 5MB.';
            } else {
                $uploadDir = __DIR__ . '/../../uploads/';
                $fileName = uniqid() . '_' . basename($_FILES['photo']['name']);
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)) {
                    $stmt = $pdo->prepare('UPDATE students SET photo = ? WHERE id = ?');
                    $stmt->execute([$fileName, $student['id']]);
                    $success = 'Profile photo updated successfully.';
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
        $new = $_POST['new_password'] ?? '';
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
                    $stmt = $pdo->prepare('UPDATE students SET password = ? WHERE id = ?');
                    $stmt->execute([$hashed, $student['id']]);
                    $success = 'Password changed successfully.';
                }
            }
        }
    }
}

$photoUrl = '/uploads/' . $student['photo'];
if (empty($student['photo'])) {
    $photoUrl = null;
}
?>

<style>
    .profile-grid { display:grid; grid-template-columns: 1fr 1fr; gap:1.5rem; }
    .profile-card { background:#fff; border:1px solid #e2e8f0; border-radius:1rem; padding:1.5rem; box-shadow:0 18px 40px rgba(15,23,42,0.04); }
    .profile-heading { margin:0 0 1rem; font-size:1.125rem; }
    .info-row { display:flex; justify-content:space-between; gap:1rem; padding:0.9rem 0; border-bottom:1px solid #f1f5f9; }
    .info-label { color:#64748b; font-weight:600; }
    .info-value { color:#0f172a; font-weight:700; }
    .photo-preview { width:100%; max-width:180px; border-radius:1rem; overflow:hidden; background:#f8fafc; display:flex; align-items:center; justify-content:center; height:180px; margin-bottom:1rem; }
    .photo-preview img { width:100%; height:100%; object-fit:cover; }
    .photo-placeholder { color:#64748b; font-weight:700; }
    .form-group { margin-bottom:1rem; }
</style>

<div class="grid-auto profile-grid">
    <div class="profile-card">
        <h2 class="profile-heading">Profile Summary</h2>
        <div class="photo-preview">
            <?php if ($photoUrl): ?>
                <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="Profile Photo">
            <?php else: ?>
                <span class="photo-placeholder">No photo</span>
            <?php endif; ?>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_photo" value="1">
            <div class="form-group">
                <label class="text-sm font-semibold" style="display:block; margin-bottom:0.5rem;">Update Profile Photo</label>
                <input type="file" name="photo" accept="image/png,image/jpeg,image/gif" class="input-field">
            </div>
            <button type="submit" class="btn-primary">Upload Photo</button>
        </form>
    </div>

    <div class="profile-card">
        <h2 class="profile-heading">Account Details</h2>
        <?php if (!empty($errors)): ?>
            <div class="card-container card-tight" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c; margin-bottom:1rem;">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="card-container card-tight" style="background:#ecfdf5; border-color:#a7f3d0; color:#047857; margin-bottom:1rem;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="info-row"><span class="info-label">Student ID</span><span class="info-value"><?php echo htmlspecialchars($student['student_id']); ?></span></div>
        <div class="info-row"><span class="info-label">Name</span><span class="info-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span></div>
        <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?php echo htmlspecialchars($student['email']); ?></span></div>
        <div class="info-row"><span class="info-label">Course / Year</span><span class="info-value"><?php echo htmlspecialchars($student['course'] . ' / ' . $student['year_level']); ?></span></div>
        <div class="info-row"><span class="info-label">Section</span><span class="info-value"><?php echo htmlspecialchars($student['section']); ?></span></div>
        <div class="info-row"><span class="info-label">Contact</span><span class="info-value"><?php echo htmlspecialchars($student['contact_number']); ?></span></div>

        <form method="POST" style="margin-top:1.5rem;">
            <input type="hidden" name="change_password" value="1">
            <h3 style="margin:0 0 1rem; font-size:1rem;">Change Password</h3>
            <div class="form-group">
                <label class="text-sm font-semibold" style="display:block; margin-bottom:0.5rem;">Current Password</label>
                <input type="password" name="current_password" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="text-sm font-semibold" style="display:block; margin-bottom:0.5rem;">New Password</label>
                <input type="password" name="new_password" class="input-field" required>
            </div>
            <div class="form-group">
                <label class="text-sm font-semibold" style="display:block; margin-bottom:0.5rem;">Confirm Password</label>
                <input type="password" name="confirm_password" class="input-field" required>
            </div>
            <button type="submit" class="btn-primary">Update Password</button>
        </form>
    </div>
</div>

<?php render_student_footer(); ?>
