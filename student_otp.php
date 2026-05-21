<?php
session_start();
require_once 'backend/student_auth.php';

if (isset($_SESSION['student_user_id'])) {
    header('Location: views/user/dashboard.php');
    exit();
}

$student = student_pending_activation_student();
if (!$student) {
    header('Location: student_login.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend_otp'])) {
        $result = student_resend_activation_otp();
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    } else {
        $otp = trim($_POST['otp'] ?? '');
        if ($otp === '') {
            $errors[] = 'OTP code is required.';
        } else {
            $result = student_verify_otp($otp);
            if ($result['success']) {
                header('Location: student_password_setup.php');
                exit();
            }
            $errors[] = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student OTP Verification — RFID System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/design-system.css">
    <style>
        body { background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .auth-card { width: 100%; max-width: 500px; margin: 1.5rem; }
        .auth-form { background:white; border:1px solid #e2e8f0; border-radius:1.25rem; box-shadow:0 18px 40px rgba(15,23,42,0.08); padding:2rem; }
        .auth-title { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.75rem; margin-bottom:0.5rem; }
        .auth-text { color:#64748b; margin-bottom:1.5rem; }
        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; margin-bottom:0.5rem; font-weight:600; color:#0f172a; }
        .input-field { width:100%; }
        .btn-group { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:1rem; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-form card-container">
            <h1 class="auth-title">Verify Your Account</h1>
            <p class="auth-text">An OTP has been sent to <strong><?php echo htmlspecialchars($student['email']); ?></strong>. Enter it below to continue account activation.</p>

            <?php if (!empty($errors)): ?>
                <div class="card-container card-tight" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c; margin-bottom:1rem;">
                    <?php foreach ($errors as $error): ?>
                        <div class="mb-2"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
                <div class="card-container card-tight" style="background:#ecfdf5; border-color:#a7f3d0; color:#047857; margin-bottom:1rem;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="otp">OTP Code</label>
                    <input type="text" id="otp" name="otp" class="input-field" placeholder="Enter 6-digit code" required>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn-primary">Verify OTP</button>
                    <button type="submit" name="resend_otp" class="btn-secondary" style="min-width: 180px;">Resend OTP</button>
                </div>
            </form>

            <p class="text-sm mt-4" style="color:#64748b;">If you did not receive the code, please check your spam folder or ask your administrator to verify your email.</p>
        </div>
    </div>
</body>
</html>
