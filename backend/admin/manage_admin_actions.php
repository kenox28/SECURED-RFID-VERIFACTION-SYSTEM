<?php
// backend/admin/manage_admin_actions.php

function get_admins(): array
{
    global $pdo;
    $stmt = $pdo->query(
        'SELECT a.id, a.username, a.fullname, a.role, a.status, a.department_id, a.created_at, d.department_name
         FROM admins a
         LEFT JOIN departments d ON a.department_id = d.id
         ORDER BY a.created_at DESC'
    );
    return $stmt->fetchAll();
}

function get_admin_by_id(int $admin_id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT a.id, a.username, a.fullname, a.role, a.status, a.department_id, a.created_at, d.department_name
         FROM admins a
         LEFT JOIN departments d ON a.department_id = d.id
         WHERE a.id = ?'
    );
    $stmt->execute([$admin_id]);
    return $stmt->fetch() ?: null;
}

function create_admin(string $username, string $fullname, string $password, string $role, ?int $department_id = null): bool
{
    global $pdo;

    if ($role !== 'super_admin' && $role !== 'admin') {
        return false;
    }

    if ($role === 'super_admin') {
        $department_id = null;
    }

    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        return false;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('INSERT INTO admins (username, password, fullname, role, department_id, status) VALUES (?, ?, ?, ?, ?, ?)');
    return $stmt->execute([$username, $hashedPassword, $fullname, $role, $department_id, 'active']);
}

function update_admin(int $admin_id, string $username, string $fullname, ?string $password, string $role, ?int $department_id, string $status): bool
{
    global $pdo;

    if ($role !== 'super_admin' && $role !== 'admin') {
        return false;
    }

    if ($role === 'super_admin') {
        $department_id = null;
    }

    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ? AND id != ?');
    $stmt->execute([$username, $admin_id]);
    if ($stmt->fetch()) {
        return false;
    }

    if ($password !== null && $password !== '') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE admins SET username = ?, fullname = ?, password = ?, role = ?, department_id = ?, status = ? WHERE id = ?');
        return $stmt->execute([$username, $fullname, $hashedPassword, $role, $department_id, $status, $admin_id]);
    }

    $stmt = $pdo->prepare('UPDATE admins SET username = ?, fullname = ?, role = ?, department_id = ?, status = ? WHERE id = ?');
    return $stmt->execute([$username, $fullname, $role, $department_id, $status, $admin_id]);
}

function delete_admin(int $admin_id): bool
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM admins WHERE id = ?');
    return $stmt->execute([$admin_id]);
}

function get_departments(): array
{
    global $pdo;
    $stmt = $pdo->query('SELECT id, department_name, department_code, created_at FROM departments ORDER BY department_name ASC');
    return $stmt->fetchAll();
}

function get_department_by_id(int $department_id): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, department_name, department_code, created_at FROM departments WHERE id = ?');
    $stmt->execute([$department_id]);
    return $stmt->fetch() ?: null;
}

function create_department(string $department_name, string $department_code): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id FROM departments WHERE department_code = ? OR department_name = ?');
    $stmt->execute([$department_code, $department_name]);
    if ($stmt->fetch()) {
        return false;
    }

    $stmt = $pdo->prepare('INSERT INTO departments (department_name, department_code) VALUES (?, ?)');
    return $stmt->execute([$department_name, $department_code]);
}

function update_department(int $department_id, string $department_name, string $department_code): bool
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT id FROM departments WHERE (department_code = ? OR department_name = ?) AND id != ?');
    $stmt->execute([$department_code, $department_name, $department_id]);
    if ($stmt->fetch()) {
        return false;
    }

    $stmt = $pdo->prepare('UPDATE departments SET department_name = ?, department_code = ? WHERE id = ?');
    return $stmt->execute([$department_name, $department_code, $department_id]);
}

function delete_department(int $department_id): bool
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM departments WHERE id = ?');
    return $stmt->execute([$department_id]);
}

function get_activity_logs(int $limit = 50): array
{
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT l.id, l.activity, l.created_at, a.username, a.role
         FROM activity_logs l
         LEFT JOIN admins a ON l.admin_id = a.id
         ORDER BY l.created_at DESC
         LIMIT ?'
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function delete_activity_log(int $log_id): bool
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM activity_logs WHERE id = ?');
    return $stmt->execute([$log_id]);
}
