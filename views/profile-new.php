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
        $stmt->execute([$_SESSION['user_id']]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($current_password, $admin['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $_SESSION['user_id']]);

            log_activity("[" . ($_SESSION['role'] ?? 'unknown') . "] " . ($_SESSION['username'] ?? 'unknown') . ' changed password');

            $success = 'Password changed successfully!';
            $new_password = $confirm_password = '';
        } else {
            $errors[] = 'Current password is incorrect';
        }
    }
}

$stmt = $pdo->prepare("SELECT username, fullname, created_at FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
?>

<div style="max-width: 600px; margin: 0 auto;">
    <!-- Alerts -->
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

    <!-- Profile Info Card -->
    <div class="card-container mb-6">
        <h3 class="text-lg-heading mb-6">Account Information</h3>
        
        <div class="pb-4 mb-4" style="border-bottom: 1px solid var(--color-border);">
            <p class="text-xs mb-1" style="color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Username</p>
            <p class="text-body font-semibold"><?php echo htmlspecialchars($admin['username']); ?></p>
        </div>

        <div class="pb-4 mb-4" style="border-bottom: 1px solid var(--color-border);">
            <p class="text-xs mb-1" style="color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Full Name</p>
            <p class="text-body font-semibold"><?php echo htmlspecialchars($admin['fullname']); ?></p>
        </div>

        <div>
            <p class="text-xs mb-1" style="color: var(--color-text-secondary); text-transform: uppercase; letter-spacing: 0.05em; font-weight: 600;">Account Created</p>
            <p class="text-body"><?php echo date('F d, Y — H:i', strtotime($admin['created_at'])); ?></p>
        </div>
    </div>

    <!-- Change Password Card -->
    <div class="card-container">
        <h3 class="text-lg-heading mb-6">Change Password</h3>
        
        <form method="POST">
            <div class="mb-4">
                <label for="current_password" class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Current Password</label>
                <input type="password" class="input-field" style="width: 100%;" id="current_password" name="current_password" required>
            </div>

            <div class="mb-4">
                <label for="new_password" class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">New Password</label>
                <input type="password" class="input-field" style="width: 100%;" id="new_password" name="new_password" required>
                <p class="text-xs mt-1" style="color: var(--color-text-secondary);">Minimum 6 characters</p>
            </div>

            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-semibold mb-2" style="color: var(--color-text-primary);">Confirm New Password</label>
                <input type="password" class="input-field" style="width: 100%;" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-primary">Update Password</button>
        </form>
    </div>
</div>

<?php render_footer(); ?>
