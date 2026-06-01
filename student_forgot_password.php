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

    if ($identifier === '') {
        $errors[] = 'Student ID or email is required.';
    }

    if (empty($errors)) {
        $student = find_student_by_identifier($identifier);
        if (!$student || $student['status'] !== 'Active') {
            $errors[] = 'Invalid student ID, email, or account not active.';
        } else {
            $result = send_student_password_reset_otp($student);
            if ($result['success']) {
                $success = $result['message'];
            } else {
                $errors[] = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — RFID System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|sora:400,500,600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:       #fb8500;
            --accent-hover: #e07600;
            --accent-glow:  rgba(251,133,0,0.12);
            --light-blue:   #caf0f8;
            --slate-50:     #f8fafc;
            --slate-100:    #f1f5f9;
            --slate-200:    #e2e8f0;
            --slate-300:    #cbd5e1;
            --slate-400:    #94a3b8;
            --slate-500:    #64748b;
            --slate-600:    #475569;
            --slate-700:    #334155;
            --slate-800:    #1e293b;
            --slate-900:    #0f172a;
            --red-50:       #fef2f2;
            --red-200:      #fecaca;
            --red-600:      #dc2626;
            --green-50:     #ecfdf5;
            --green-200:    #a7f3d0;
            --green-700:    #047857;
        }

        body {
            font-family: 'Sora', sans-serif;
            background-color: var(--slate-50);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            opacity: 0.4;
            pointer-events: none;
        }
        body::before {
            width: 500px; height: 500px;
            background: var(--light-blue);
            top: -150px; right: -150px;
        }
        body::after {
            width: 400px; height: 400px;
            background: #fff3e0;
            bottom: -120px; left: -120px;
        }

        /* ── CARD ── */
        .card {
            background: #fff;
            border-radius: 1.5rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            width: 100%; max-width: 440px;
            padding: 2.5rem;
            position: relative; z-index: 1;
            animation: slideUp .4s cubic-bezier(.22,1,.36,1) both;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* ── BACK LINK ── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            font-weight: 500;
            color: var(--slate-500);
            text-decoration: none;
            margin-bottom: 1.75rem;
            transition: color .15s;
        }

        .back-link svg { width: 14px; height: 14px; stroke: currentColor; }
        .back-link:hover { color: var(--accent); }

        /* ── HEADER ── */
        .card-header { text-align: center; margin-bottom: 2rem; }

        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px; height: 64px;
            border-radius: 1rem;
            background: #fff7ed;
            border: 1.5px solid #fed7aa;
            margin-bottom: 1.25rem;
        }

        .icon-wrap svg { width: 30px; height: 30px; stroke: var(--accent); }

        .card-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--slate-900);
            letter-spacing: -0.02em;
            margin-bottom: .375rem;
        }

        .card-header p { font-size: .875rem; color: var(--slate-400); line-height: 1.6; }

        /* ── ALERTS ── */
        .alert {
            border-radius: .75rem;
            padding: .875rem 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid;
        }

        .alert ul { list-style: none; display: flex; flex-direction: column; gap: .25rem; }

        .alert li {
            font-size: .8125rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: .5rem;
        }

        .alert li::before {
            content: '';
            display: inline-block;
            width: 6px; height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .alert-error { background: var(--red-50); border-color: var(--red-200); color: var(--red-600); }
        .alert-error li::before { background: var(--red-600); }

        .alert-success { background: var(--green-50); border-color: var(--green-200); color: var(--green-700); }
        .alert-success p { font-size: .8125rem; font-weight: 500; }

        /* ── SUCCESS CTA ── */
        .success-cta {
            margin-top: .75rem;
            padding-top: .75rem;
            border-top: 1px solid var(--green-200);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .success-cta span { font-size: .78rem; color: var(--green-700); }

        .btn-success {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: var(--green-700);
            color: #fff;
            text-decoration: none;
            border-radius: .6rem;
            padding: .45rem 1rem;
            font-size: .8rem;
            font-weight: 600;
            font-family: 'Plus Jakarta Sans', sans-serif;
            transition: background .15s;
            white-space: nowrap;
        }

        .btn-success:hover { background: #065f46; }

        /* ── FORM ── */
        .form-group { margin-bottom: 1.25rem; }

        label {
            display: block;
            font-size: .8125rem;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: .5rem;
        }

        .input-wrap { position: relative; }

        .input-wrap .field-icon {
            position: absolute;
            left: .875rem; top: 50%; transform: translateY(-50%);
            width: 16px; height: 16px;
            stroke: var(--slate-400);
            pointer-events: none;
        }

        input[type="text"] {
            width: 100%;
            padding: .6875rem .875rem .6875rem 2.5rem;
            border: 1px solid var(--slate-200);
            border-radius: .75rem;
            font-family: 'Sora', sans-serif;
            font-size: .875rem;
            color: var(--slate-900);
            background: var(--slate-50);
            outline: none;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        input:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        input::placeholder { color: var(--slate-300); }

        /* ── BUTTON ── */
        .btn-primary {
            width: 100%;
            padding: .75rem 1.5rem;
            background: var(--accent);
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .9375rem;
            font-weight: 700;
            border: none;
            border-radius: .75rem;
            cursor: pointer;
            transition: background .15s, transform .1s, box-shadow .15s;
            letter-spacing: .01em;
            margin-top: .25rem;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            box-shadow: 0 4px 12px rgba(251,133,0,0.35);
        }

        .btn-primary:active { transform: scale(0.98); }

        /* ── LINKS ROW ── */
        .links-row {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 1.25rem;
        }

        .text-link {
            font-size: .8rem;
            color: var(--slate-500);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: .3rem;
            transition: color .15s;
        }

        .text-link svg { width: 13px; height: 13px; stroke: currentColor; }
        .text-link:hover { color: var(--accent); }

        /* ── DIVIDER ── */
        .divider {
            display: flex;
            align-items: center;
            gap: .75rem;
            margin: 1.5rem 0 0;
        }

        .divider::before,
        .divider::after { content: ''; flex: 1; height: 1px; background: var(--slate-100); }

        .divider span { font-size: .75rem; color: var(--slate-400); white-space: nowrap; }

        /* ── FOOTER ── */
        .card-footer { text-align: center; margin-top: 1.25rem; }
        .card-footer p { font-size: .75rem; color: var(--slate-400); }
        .card-footer strong { color: var(--slate-600); font-weight: 600; }
    </style>
</head>
<body>

<div class="card">

    <a href="student_login.php" class="back-link">
        <svg fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Back to login
    </a>

    <div class="card-header">
        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <h1>Forgot Password</h1>
        <p>Enter your Student ID or registered email and we'll send a reset code to your inbox.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success">
            <p><?= htmlspecialchars($success) ?></p>
            <div class="success-cta">
                <span>Check your inbox and enter the code.</span>
                <a href="student_reset_password.php" class="btn-success">
                    Enter Code
                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="identifier">Student ID or Email</label>
            <div class="input-wrap">
                <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <input
                    type="text"
                    id="identifier"
                    name="identifier"
                    placeholder="e.g. 2024-00001 or email"
                    value="<?= htmlspecialchars($_POST['identifier'] ?? '') ?>"
                    required
                >
            </div>
        </div>

        <button type="submit" class="btn-primary">Send Reset Code</button>
    </form>

    <div class="links-row">
        <a href="student_login.php" class="text-link">
            <svg fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Remember your password? Sign in
        </a>
    </div>

    <div class="divider"><span>RFID System v1.0</span></div>

    <div class="card-footer">
        <p>Reset codes expire in 10 minutes · <strong>Check spam if not received</strong></p>
    </div>

</div>

</body>
</html>