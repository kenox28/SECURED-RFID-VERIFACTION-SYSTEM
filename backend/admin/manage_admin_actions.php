<?php
// backend/admin/manage_admin_actions.php

function get_admins(): array
{
    global $pdo;

    $stmt = $pdo->query(
        'SELECT 
            a.id, 
            a.username, 
            a.fullname, 
            a.role, 
            a.status, 
            a.department_id, 
            a.created_at, 
            d.department_name
         FROM admins a
         LEFT JOIN departments d ON a.department_id = d.id
         ORDER BY a.created_at DESC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_admin(string $username, string $fullname, string $password, string $role, ?int $department_id = null): bool
{
    global $pdo;

    if (!in_array($role, ['super_admin', 'admin'])) {
        return false;
    }

    if ($role === 'super_admin') {
        $department_id = null;
    }

    // check duplicate username
    $stmt = $pdo->prepare('SELECT id FROM admins WHERE username = ?');
    $stmt->execute([$username]);

    if ($stmt->fetch()) {
        return false;
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare(
        'INSERT INTO admins (username, password, fullname, role, department_id, status) 
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    return $stmt->execute([
        $username,
        $hashedPassword,
        $fullname,
        $role,
        $department_id,
        'active'
    ]);
}

function get_departments(): array
{
    global $pdo;

    // FIXED: removed created_at (causing your error)
    $stmt = $pdo->query(
        'SELECT 
            id, 
            department_name, 
            department_code 
         FROM departments 
         ORDER BY department_name ASC'
    );

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function create_department(string $department_name, string $department_code): bool
{
    global $pdo;

    // check duplicates
    $stmt = $pdo->prepare(
        'SELECT id FROM departments 
         WHERE department_code = ? OR department_name = ?'
    );
    $stmt->execute([$department_code, $department_name]);

    if ($stmt->fetch()) {
        return false;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO departments (department_name, department_code) 
         VALUES (?, ?)'
    );

    return $stmt->execute([$department_name, $department_code]);
}

function get_activity_logs(int $limit = 50): array
{
    global $pdo;

    $stmt = $pdo->prepare(
        'SELECT 
            l.id, 
            l.activity, 
            l.created_at, 
            a.username, 
            a.role
         FROM activity_logs l
         LEFT JOIN admins a ON l.admin_id = a.id
         ORDER BY l.created_at DESC
         LIMIT ?'
    );

    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}