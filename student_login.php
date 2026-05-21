<?php
session_start();
require_once 'backend/student_auth.php';

if (isset($_SESSION['student_user_id'])) {
    header('Location: views/user/dashboard.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($identifier === '') {
        $errors[] = 'Student ID or email is required.';
    }

    if (empty($errors)) {
        $result = student_login($identifier, $password);
        if ($result['success']) {
            if (isset($_SESSION['student_pending_activation'])) {
                header('Location: student_otp.php');
                exit();
            }

            header('Location: views/user/dashboard.php');
            exit();
        }

        $errors[] = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login — RFID System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/design-system.css">
    <style>
        body { background: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .auth-card { width: 100%; max-width: 480px; margin: 1.5rem; }
        .logo-badge { display:inline-flex; align-items:center; gap:0.75rem; margin-bottom:1rem; }
        .logo-icon { width: 52px; height:52px; background: linear-gradient(135deg, #fb8500, #ffb703); border-radius: 1rem; display:flex; align-items:center; justify-content:center; color:white; font-size:1.25rem; font-weight:700; }
        .auth-form { background:white; border:1px solid #e2e8f0; border-radius:1.25rem; box-shadow:0 18px 40px rgba(15,23,42,0.08); padding:2rem; }
        .auth-title { font-family:'Plus Jakarta Sans',sans-serif; font-size:1.75rem; margin-bottom:0.5rem; }
        .auth-text { color:#64748b; margin-bottom:1.5rem; }
        .form-group { margin-bottom:1rem; }
        .form-group label { display:block; margin-bottom:0.5rem; font-weight:600; color:#0f172a; }
        .input-field { width:100%; }
        .submit-row { display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        .card-alert { margin-bottom:1rem; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-form card-container">
            <div class="logo-badge">
                <div class="logo-icon">RF</div>
                <div>
                    <h1 class="auth-title">Student Login</h1>
                    <p class="auth-text">Enter your student ID or registered email to continue.</p>
                </div>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="card-container card-tight card-spacious" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c;">
                    <?php foreach ($errors as $error): ?>
                        <div class="mb-2"><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="mt-3">
                <div class="form-group">
                    <label for="identifier">Student ID or Email</label>
                    <input type="text" id="identifier" name="identifier" class="input-field" value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password <span style="font-size:0.85rem; color:#94a3b8;">(Leave blank for first-time login)</span></label>
                    <input type="password" id="password" name="password" class="input-field" autocomplete="current-password">
                </div>
                <div class="submit-row">
                    <button type="submit" class="btn-primary">Continue</button>
                    <a href="login.php" class="btn-secondary" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">Admin Login</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
