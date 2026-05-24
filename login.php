<?php
session_start();

require_once 'backend/auth.php';

if (isset($_SESSION['user_id'])) {
    header('Location: views/dashboard.php');
    exit();
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($username === '') {
        $errors[] = 'Username is required';
    }
    if ($password === '') {
        $errors[] = 'Password is required';
    }

    if (empty($errors)) {
        if (user_login($username, $password, $remember)) {
            header('Location: views/dashboard.php');
            exit();
        }

        $errors[] = 'Invalid username or password or account inactive';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — RFID System</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800|sora:400,500,600&display=swap" rel="stylesheet" />
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --accent:       #fb8500;
            --accent-hover: #e07600;
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

        /* Decorative background blobs */
        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            opacity: 0.4;
            pointer-events: none;
        }
        body::before {
            width: 500px;
            height: 500px;
            background: var(--light-blue);
            top: -150px;
            right: -150px;
        }
        body::after {
            width: 400px;
            height: 400px;
            background: #fff3e0;
            bottom: -120px;
            left: -120px;
        }

        /* Card */
        .card {
            background: #fff;
            border-radius: 1.5rem;
            border: 1px solid var(--slate-200);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            width: 100%;
            max-width: 420px;
            padding: 2.5rem;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .card-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 1rem;
            background: var(--light-blue);
            margin-bottom: 1.25rem;
        }

        .icon-wrap svg {
            width: 32px;
            height: 32px;
            color: var(--slate-700);
            stroke: var(--slate-700);
        }

        .card-header h1 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--slate-900);
            letter-spacing: -0.02em;
            margin-bottom: 0.375rem;
        }

        .card-header p {
            font-size: 0.875rem;
            color: var(--slate-400);
        }

        /* Error alert */
        .alert {
            background: var(--red-50);
            border: 1px solid var(--red-200);
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.5rem;
        }

        .alert ul {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .alert li {
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--red-600);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert li::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--red-600);
            flex-shrink: 0;
        }

        /* Form */
        .form-group {
            margin-bottom: 1.25rem;
        }

        label {
            display: block;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--slate-700);
            margin-bottom: 0.5rem;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap svg {
            position: absolute;
            left: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            stroke: var(--slate-400);
            pointer-events: none;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.6875rem 0.875rem 0.6875rem 2.5rem;
            border: 1px solid var(--slate-200);
            border-radius: 0.75rem;
            font-family: 'Sora', sans-serif;
            font-size: 0.875rem;
            color: var(--slate-900);
            background: var(--slate-50);
            outline: none;
            transition: border-color 0.15s, box-shadow 0.15s, background 0.15s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: var(--accent);
            background: #fff;
            box-shadow: 0 0 0 3px rgba(251, 133, 0, 0.12);
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder {
            color: var(--slate-300);
        }

        /* Password toggle */
        .toggle-password {
            position: absolute;
            right: 0.875rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
        }

        .toggle-password svg {
            position: static;
            transform: none;
            width: 16px;
            height: 16px;
            stroke: var(--slate-400);
            transition: stroke 0.15s;
        }

        .toggle-password:hover svg {
            stroke: var(--slate-600);
        }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 0.625rem;
            margin-bottom: 1.5rem;
        }

        .remember-row input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--accent);
            cursor: pointer;
            flex-shrink: 0;
        }

        .remember-row label {
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--slate-500);
            cursor: pointer;
        }

        /* Submit button */
        .btn-primary {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: var(--accent);
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 0.9375rem;
            font-weight: 700;
            border: none;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: background 0.15s, transform 0.1s, box-shadow 0.15s;
            letter-spacing: 0.01em;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
            box-shadow: 0 4px 12px rgba(251, 133, 0, 0.35);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        /* Divider */
        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.5rem 0 0;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--slate-100);
        }

        .divider span {
            font-size: 0.75rem;
            color: var(--slate-400);
            white-space: nowrap;
        }

        /* Footer */
        .card-footer {
            text-align: center;
            margin-top: 1.5rem;
        }

        .card-footer p {
            font-size: 0.75rem;
            color: var(--slate-400);
        }

        .card-footer strong {
            color: var(--slate-600);
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="card">

        <div class="card-header">
            <div class="icon-wrap">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                        d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>
            <h1>Admin Login</h1>
            <p>Secured Student Management System</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="">

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrap">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        placeholder="Enter your username"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                    >
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrap">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Enter your password"
                        autocomplete="current-password"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword()" title="Show/hide password">
                        <svg id="eye-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="remember-row">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Remember me</label>
            </div>

            <button type="submit" class="btn-primary">Sign In</button>

        </form>

        <div class="divider"><span>RFID System v1.0</span></div>

        <div class="card-footer">
            <p>Authorized personnel only · <strong>Admin Portal</strong></p>
        </div>

    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon  = document.getElementById('eye-icon');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.innerHTML = isHidden
                ? `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>`
                : `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>`;
        }
    </script>

</body>
</html>