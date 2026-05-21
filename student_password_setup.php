<?php
session_start();
require_once 'backend/student_auth.php';

$student = student_pending_activation_student();
if (!$student || empty($_SESSION['student_activation_verified'])) {
    header('Location: student_login.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $errors[] = 'New password and confirmation do not match.';
    }

    if (empty($errors)) {
        $result = student_activate_account($password);
        if ($result['success']) {
            header('Location: views/user/dashboard.php');
            exit();
        }

        if (!empty($result['errors'])) {
            $errors = array_merge($errors, $result['errors']);
        } else {
            $errors[] = $result['message'] ?? 'Unable to create password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Password — RFID System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/design-system.css">
    <style>
        body { background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .auth-card { width: 100%; max-width: 520px; margin: 1.5rem; }
        .auth-form { background:white; border:1px solid #e2e8f0; border-radius:1.25rem; box-shadow:0 18px 40px rgba(15,23,42,0.08); padding:2rem; }
        .auth-title { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.75rem; margin-bottom:0.5rem; }
        .auth-text { color:#64748b; margin-bottom:1.5rem; }
        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; margin-bottom:0.5rem; font-weight:600; color:#0f172a; }
        .input-field { width:100%; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-form card-container">
            <h1 class="auth-title">Create Your Password</h1>
            <p class="auth-text">Set a secure password for your student account. This password will be used for future logins.</p>

            <?php if (!empty($errors)): ?>
                <div class="card-container card-tight" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c; margin-bottom:1rem;">
                    <?php foreach ($errors as $error): ?>
                        <div class="mb-2"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" class="input-field" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="input-field" required>
                </div>
                <button type="submit" class="btn-primary">Save Password</button>
            </form>

            <p class="text-sm mt-4" style="color:#64748b;">Your password must be at least 8 characters and include uppercase, lowercase, a number, and a special symbol.</p>
        </div>
    </div>
</body>
</html>
