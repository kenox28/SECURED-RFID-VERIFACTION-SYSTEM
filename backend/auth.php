<?php
// backend/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';


function ensure_user_session(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit();
    }

    $user = get_logged_user();
    if (!$user || $user['status'] !== 'active' || !in_array($user['role'], ['super_admin', 'admin'], true)) {
        user_logout();
        header('Location: /login.php');
        exit();
    }

    // Keep session values in sync with database
    $_SESSION['username'] = $user['username'];
    $_SESSION['fullname'] = $user['fullname'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['department_id'] = $user['department_id'];
}

function user_login(string $username, string $password, bool $remember = false): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, username, password, fullname, role, department_id, status FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if (!$user || $user['status'] !== 'active' || !in_array($user['role'], ['super_admin', 'admin'], true)) {
        return false;
    }

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['fullname'] = $user['fullname'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department_id'] = $user['department_id'];

        if ($remember) {
            setcookie('admin_login', session_id(), time() + 30 * 24 * 60 * 60, '/');
        }

        log_activity("[{$user['role']}] {$user['username']} logged in");

        return true;
    }

    return false;
}

function user_logout(): void
{
    if (isset($_SESSION['user_id'])) {
        log_activity("[" . ($_SESSION['role'] ?? 'unknown') . "] " . ($_SESSION['username'] ?? 'unknown') . ' logged out');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }

    session_destroy();

    if (isset($_COOKIE['admin_login'])) {
        setcookie('admin_login', '', time() - 3600, '/');
    }
}

function get_logged_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, username, fullname, role, department_id, status FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function is_super_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin';
}

function is_department_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function get_department_filter(string $alias = '', bool $allowNull = false): array
{
    if (is_super_admin()) {
        return ['', []];
    }

    $prefix = $alias !== '' ? $alias . '.' : '';
    if ($allowNull) {
        return ["({$prefix}department_id = ? OR {$prefix}department_id IS NULL)", [$_SESSION['department_id']]];
    }

    return ["{$prefix}department_id = ?", [$_SESSION['department_id']]];
}

function log_activity(string $message): void
{
    global $pdo;
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare('INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)');
        $stmt->execute([$userId, $message]);
    }
}
