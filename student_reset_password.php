<?php
session_start();
require_once 'backend/student_auth.php';

if (isset($_SESSION['student_user_id'])) {
    header('Location: views/user/dashboard.php');
    exit();
}

$student = student_password_reset_student();
if (!$student) {
    header('Location: student_forgot_password.php');
    exit();
}

$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['resend_code'])) {
        $result = student_resend_password_reset_otp();
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    } else {
        $otp             = trim($_POST['otp'] ?? '');
        $password        = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if ($otp === '')                           $errors[] = 'Verification code is required.';
        if ($password === '' || $confirmPassword === '') $errors[] = 'New password and confirmation are required.';
        elseif ($password !== $confirmPassword)    $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $verifyResult = student_verify_password_reset_otp($otp);
            if (!$verifyResult['success']) {
                $errors[] = $verifyResult['message'];
            } else {
                $resetResult = student_reset_password($password);
                if ($resetResult['success']) {
                    header('Location: views/user/dashboard.php');
                    exit();
                }
                if (!empty($resetResult['errors'])) {
                    $errors = array_merge($errors, $resetResult['errors']);
                } else {
                    $errors[] = $resetResult['message'] ?? 'Unable to reset your password.';
                }
            }
        }
    }
}

// Mask email
$email      = $student['email'] ?? '';
$parts      = explode('@', $email);
$masked     = (strlen($parts[0]) > 2)
    ? substr($parts[0], 0, 2) . str_repeat('*', strlen($parts[0]) - 2) . '@' . ($parts[1] ?? '')
    : $email;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — RFID System</title>
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
            width: 100%; max-width: 460px;
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
        .card-header { text-align: center; margin-bottom: 1.75rem; }

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

        /* ── EMAIL CHIP ── */
        .email-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--slate-100);
            border: 1px solid var(--slate-200);
            border-radius: .5rem;
            padding: .35rem .75rem;
            font-size: .78rem;
            color: var(--slate-600);
            font-weight: 500;
            margin-top: .5rem;
        }

        .email-chip svg { width: 13px; height: 13px; stroke: var(--slate-400); }

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

        .alert-error   { background: var(--red-50);   border-color: var(--red-200);   color: var(--red-600); }
        .alert-error li::before { background: var(--red-600); }

        .alert-success { background: var(--green-50); border-color: var(--green-200); color: var(--green-700); }
        .alert-success p { font-size: .8125rem; font-weight: 500; }
        .alert-success li::before { background: var(--green-700); }

        /* ── SECTION DIVIDER ── */
        .section-divider {
            display: flex;
            align-items: center;
            gap: .6rem;
            margin: 1.5rem 0 1.25rem;
        }

        .section-divider::before,
        .section-divider::after { content: ''; flex: 1; height: 1px; background: var(--slate-200); }

        .section-divider span {
            font-size: .72rem;
            font-weight: 600;
            color: var(--slate-400);
            text-transform: uppercase;
            letter-spacing: .06em;
            white-space: nowrap;
        }

        /* ── OTP INPUT ── */
        .otp-input {
            width: 100%;
            padding: .85rem 1rem;
            border: 1px solid var(--slate-200);
            border-radius: .75rem;
            font-family: 'Plus Jakarta Sans', monospace;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--slate-900);
            background: var(--slate-50);
            outline: none;
            text-align: center;
            letter-spacing: .35em;
            transition: border-color .15s, box-shadow .15s, background .15s;
        }

        .otp-input:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        .otp-input::placeholder {
            font-size: .9rem;
            letter-spacing: .05em;
            color: var(--slate-300);
            font-weight: 400;
        }

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

        input[type="password"] {
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

        input[type="password"]:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px var(--accent-glow);
        }

        input::placeholder { color: var(--slate-300); }

        /* password visibility toggle */
        .toggle-password {
            position: absolute;
            right: .875rem; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            padding: 0; display: flex; align-items: center;
        }

        .toggle-password svg { width: 16px; height: 16px; stroke: var(--slate-400); transition: stroke .15s; }
        .toggle-password:hover svg { stroke: var(--slate-600); }

        /* password rules */
        .pwd-rules {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: .25rem .5rem;
            margin-top: .6rem;
        }

        .pwd-rule {
            font-size: .72rem;
            color: var(--slate-400);
            display: flex;
            align-items: center;
            gap: .3rem;
        }

        .pwd-rule::before {
            content: '';
            display: inline-block;
            width: 4px; height: 4px;
            border-radius: 50%;
            background: var(--slate-300);
            flex-shrink: 0;
        }

        /* ── BUTTONS ── */
        .btn-row {
            display: flex;
            gap: .75rem;
            margin-top: .5rem;
        }

        .btn-primary {
            flex: 1;
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
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            box-shadow: 0 4px 12px rgba(251,133,0,0.35);
        }

        .btn-primary:active { transform: scale(0.98); }

        .btn-ghost {
            padding: .75rem 1.25rem;
            background: #fff;
            color: var(--slate-600);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: .875rem;
            font-weight: 600;
            border: 1px solid var(--slate-200);
            border-radius: .75rem;
            cursor: pointer;
            transition: background .15s, border-color .15s, color .15s;
            white-space: nowrap;
        }

        .btn-ghost:hover {
            background: var(--slate-100);
            border-color: var(--slate-300);
            color: var(--slate-800);
        }

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
        .card-footer a { color: var(--slate-500); font-weight: 500; text-decoration: none; transition: color .15s; }
        .card-footer a:hover { color: var(--accent); }
    </style>
</head>
<body>

<div class="card">

    <a href="student_forgot_password.php" class="back-link">
        <svg fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Change identifier
    </a>

    <div class="card-header">
        <div class="icon-wrap">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
        <h1>Reset Password</h1>
        <div class="email-chip">
            <svg fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Code sent to <?= htmlspecialchars($masked) ?>
        </div>
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
        </div>
    <?php endif; ?>

    <form method="POST">

        <!-- OTP -->
        <div class="form-group">
            <label for="otp">Verification Code</label>
            <input
                type="text"
                id="otp"
                name="otp"
                class="otp-input"
                placeholder="Enter 6-digit code"
                maxlength="6"
                inputmode="numeric"
                autocomplete="one-time-code"
                value="<?= htmlspecialchars($_POST['otp'] ?? '') ?>"
                required
            >
        </div>

        <div class="section-divider"><span>New Password</span></div>

        <!-- New password -->
        <div class="form-group">
            <label for="password">New Password</label>
            <div class="input-wrap">
                <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Create a strong password"
                    autocomplete="new-password"
                    required
                >
                <button type="button" class="toggle-password" onclick="togglePass('password','eye1')" title="Show/hide">
                    <svg id="eye1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
            <div class="pwd-rules">
                <div class="pwd-rule">At least 8 characters</div>
                <div class="pwd-rule">One uppercase letter</div>
                <div class="pwd-rule">One number</div>
                <div class="pwd-rule">One special character</div>
            </div>
        </div>

        <!-- Confirm password -->
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <div class="input-wrap">
                <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    placeholder="Re-enter your password"
                    autocomplete="new-password"
                    required
                >
                <button type="button" class="toggle-password" onclick="togglePass('confirm_password','eye2')" title="Show/hide">
                    <svg id="eye2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn-primary">Reset Password</button>
            <button type="submit" name="resend_code" value="1" class="btn-ghost">Resend Code</button>
        </div>

    </form>

    <div class="divider"><span>RFID System v1.0</span></div>

    <div class="card-footer">
        <p>Code expires in 10 minutes · <a href="student_forgot_password.php">Request a new one</a></p>
    </div>

</div>

<script>
const EYE_OPEN  = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
const EYE_SHUT  = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`;

function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    icon.innerHTML = show ? EYE_SHUT : EYE_OPEN;
}

// OTP: numbers only
document.getElementById('otp').addEventListener('input', function() {
    this.value = this.value.replace(/[^0-9]/g, '');
});
</script>

</body>
</html>