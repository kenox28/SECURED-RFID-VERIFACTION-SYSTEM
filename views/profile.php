<?php
$page_title = 'Profile';
require_once __DIR__ . '/layout.php';
render_header($page_title);

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password)) {
        $errors[] = 'Current password is required';
    }
    if (empty($new_password)) {
        $errors[] = 'New password is required';
    }
    if (strlen($new_password) < 6) {
        $errors[] = 'New password must be at least 6 characters long';
    }
    if ($new_password !== $confirm_password) {
        $errors[] = 'New password and confirmation do not match';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($current_password, $admin['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['admin_id']]);

            $stmt = $pdo->prepare("INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)");
            $stmt->execute([$_SESSION['admin_id'], 'Changed password']);

            $success = 'Password changed successfully!';
        } else {
            $errors[] = 'Current password is incorrect';
        }
    }
}

$stmt = $pdo->prepare("SELECT username, fullname, created_at FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['admin_id']]);
$admin = $stmt->fetch();
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5>Admin Profile</h5>
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

                <div class="mb-4">
                    <h6>Profile Information</h6>
                    <div class="row">
                        <div class="col-sm-3"><strong>Username:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($admin['username']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3"><strong>Full Name:</strong></div>
                        <div class="col-sm-9"><?php echo htmlspecialchars($admin['fullname']); ?></div>
                    </div>
                    <div class="row">
                        <div class="col-sm-3"><strong>Account Created:</strong></div>
                        <div class="col-sm-9"><?php echo date('M d, Y H:i', strtotime($admin['created_at'])); ?></div>
                    </div>
                </div>

                <hr>

                <h6>Change Password</h6>
                <form method="POST">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php render_footer(); ?>