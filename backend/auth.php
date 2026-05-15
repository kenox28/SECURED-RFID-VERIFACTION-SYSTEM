<?php
// backend/auth.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

function ensure_admin_session(): void
{
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /login.php');
        exit();
    }
}

function admin_login(string $username, string $password, bool $remember = false): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id, username, password, fullname FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_fullname'] = $admin['fullname'];

        if ($remember) {
            setcookie('admin_login', session_id(), time() + 30 * 24 * 60 * 60, '/');
        }

        $stmt = $pdo->prepare('INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)');
        $stmt->execute([$admin['id'], 'Admin logged in']);

        return true;
    }

    return false;
}

function admin_logout(): void
{
    if (isset($_SESSION['admin_id'])) {
        global $pdo;
        $stmt = $pdo->prepare('INSERT INTO activity_logs (admin_id, activity) VALUES (?, ?)');
        $stmt->execute([$_SESSION['admin_id'], 'Admin logged out']);
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

function get_logged_admin(): ?array
{
    if (!isset($_SESSION['admin_id'])) {
        return null;
    }

    global $pdo;
    $stmt = $pdo->prepare('SELECT id, username, fullname, role FROM admins WHERE id = ?');
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch() ?: null;
}
