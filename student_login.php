<?php
session_start();
require_once 'backend/student_auth.php';

if (isset($_SESSION['student_user_id'])) {
    header('Location: views/user/dashboard.php');
    exit();
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Sora:wght@400;500;600&display=swap" rel="stylesheet">
    <style>

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Sora', sans-serif;
            background: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
        }

        /* subtle dot grid */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image: radial-gradient(#e2e8f0 1px, transparent 1px);
            background-size: 24px 24px;
            pointer-events: none;
            z-index: 0;
        }

        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
        }

        /* Brand row */
        .brand-row {
            display: flex;
            align-items: center;
            gap: 0.875rem;
            margin-bottom: 1.75rem;
        }

        .brand-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #fb8500, #ffb703);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1rem;
            font-weight: 800;
            flex-shrink: 0;
            box-shadow: 0 4px 14px rgba(251,133,0,0.35);
        }

        .brand-text h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.05rem;
            font-weight: 700;
            color: #0f172a;
            letter-spacing: -0.01em;
            margin: 0;
        }

        .brand-text p {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 0.15rem;
        }

        /* Card */
        .login-card {
            background: #fff;
            border: 1px solid #e8edf4;
            border-radius: 1.5rem;
            box-shadow:
                0 1px 2px rgba(15,23,42,0.04),
                0 8px 32px rgba(15,23,42,0.08);
            padding: 2rem;
        }

        .card-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            margin-bottom: 0.4rem;
        }

        .card-subheading {
            font-size: 0.8125rem;
            color: #94a3b8;
            margin-bottom: 1.75rem;
            line-height: 1.6;
        }

        /* Alert */
        .alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 0.875rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
            color: #b91c1c;
            font-size: 0.8125rem;
            font-weight: 500;
        }

        .alert-error div + div {
            margin-top: 0.3rem;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.1rem;
        }

        .form-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.45rem;
        }

        .form-hint {
            font-size: 0.72rem;
            color: #94a3b8;
            font-weight: 400;
            margin-left: 0.35rem;
        }

        .form-input {
            width: 100%;
            border: 1.5px solid #e2e8f0;
            border-radius: 0.875rem;
            padding: 0.8rem 1rem;
            font-size: 0.875rem;
            color: #0f172a;
            background: #fff;
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: 'Sora', sans-serif;
        }

        .form-input:focus {
            border-color: #fb8500;
            box-shadow: 0 0 0 3px rgba(251,133,0,0.1);
        }

        .form-input::placeholder {
            color: #cbd5e1;
        }

        /* Submit button */
        .submit-btn {
            width: 100%;
            height: 3rem;
            border: none;
            background: linear-gradient(135deg, #fb8500, #f97316);
            color: #fff;
            border-radius: 0.875rem;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.15s;
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.01em;
            margin-top: 0.5rem;
            box-shadow: 0 4px 14px rgba(251,133,0,0.3);
        }

        .submit-btn:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        /* Footer note */
        .login-footer {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }

    </style>
</head>
<body>

<div class="login-wrap">

    <div class="brand-row">
        <div class="brand-icon">RF</div>
        <div class="brand-text">
            <h1>RFID Attendance System</h1>
            <p>Student Portal</p>
        </div>
    </div>

    <div class="login-card">

        <h2 class="card-heading">Welcome back</h2>
        <p class="card-subheading">Sign in with your Student ID or registered email address.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert-error">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <div class="form-group">
                <label class="form-label" for="identifier">Student ID or Email</label>
                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    class="form-input"
                    placeholder="e.g. 2024-00123 or you@school.edu"
                    value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    Password
                    <span class="form-hint">Leave blank for first-time login</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="form-input"
                    placeholder="Enter your password"
                    autocomplete="current-password"
                >
            </div>

            <button type="submit" class="submit-btn">Sign In</button>

        </form>

    </div>

    <div class="login-footer">
        Having trouble? Contact your administrator.
    </div>

</div>

</body>
</html>