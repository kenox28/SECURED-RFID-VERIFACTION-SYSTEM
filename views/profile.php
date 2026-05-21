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

            log_activity("[" . ($_SESSION['role'] ?? 'unknown') . "] " . ($_SESSION['username'] ?? 'unknown') . " changed password");

            $success = 'Password changed successfully!';
        } else {
            $errors[] = 'Current password is incorrect';
        }
    }
}

$stmt = $pdo->prepare("SELECT username, fullname, created_at FROM admins WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch();
?>

<style>
.profile-wrap {
    padding: 1.5rem;
    font-family: 'Sora', sans-serif;
    background: #f8fafc;
    min-height: 100%;
}

/* GRID */
.profile-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 768px) {
    .profile-grid {
        grid-template-columns: 1fr;
    }
}

/* CARDS */
.profile-card {
    background: #fff;
    border: 1px solid #f1f5f9;
    border-radius: 1.25rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    padding: 1.5rem;
}

.profile-title {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 1rem;
}

/* INFO ROWS */
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 0.65rem 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    font-size: 0.75rem;
    color: #94a3b8;
    font-weight: 600;
}

.info-value {
    font-size: 0.875rem;
    color: #0f172a;
    font-weight: 600;
}

/* ALERTS */
.alert {
    padding: 0.9rem 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1rem;
    font-size: 0.875rem;
}

.alert-danger {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #a7f3d0;
}

/* FORM */
.form-group {
    margin-bottom: 1rem;
}

label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    color: #475569;
    margin-bottom: 0.4rem;
}

input {
    width: 100%;
    padding: 0.75rem 0.9rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    outline: none;
    transition: 0.15s;
}

input:focus {
    border-color: #fb8500;
    box-shadow: 0 0 0 3px rgba(251,133,0,0.15);
}

/* BUTTON */
.btn-primary {
    background: #fb8500;
    color: #fff;
    border: none;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
    transition: 0.15s;
}

.btn-primary:hover {
    background: #e07600;
}
</style>

<div class="profile-wrap">

    <!-- ALERTS -->
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <div><?= htmlspecialchars($error) ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="profile-grid">

        <!-- PROFILE INFO CARD -->
        <div class="profile-card">
            <div class="profile-title">Admin Profile</div>

            <div class="info-row">
                <span class="info-label">Username</span>
                <span class="info-value"><?= htmlspecialchars($admin['username']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Full Name</span>
                <span class="info-value"><?= htmlspecialchars($admin['fullname']) ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Account Created</span>
                <span class="info-value">
                    <?= date('M d, Y H:i', strtotime($admin['created_at'])) ?>
                </span>
            </div>
        </div>

        <!-- CHANGE PASSWORD CARD -->
        <div class="profile-card">
            <div class="profile-title">Change Password</div>

            <form method="POST">

                <div class="form-group">
                    <label>Current Password</label>
                    <input type="password" name="current_password" required>
                </div>

                <div class="form-group">
                    <label>New Password</label>
                    <input type="password" name="new_password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" required>
                </div>

                <button class="btn-primary">Update Password</button>
            </form>
        </div>

    </div>
</div>

<?php render_footer(); ?>