<?php
// backend/admin/student_actions.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';

function get_students(string $search = '', int $page = 1, int $per_page = 10): array
{
    global $pdo;

    $where = [];
    $params = [];

    if (trim($search) !== '') {
        $search_param = '%' . trim($search) . '%';
        $where[] = '(student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR course LIKE ?)';
        $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    }

    if (!is_super_admin()) {
        $where[] = 'department_id = ?';
        $params[] = $_SESSION['department_id'];
    }

    $where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

    $count_sql = "SELECT COUNT(*) as total FROM students $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetch()['total'];
    $total_pages = max(1, (int)ceil($total_records / $per_page));
    $offset = ($page - 1) * $per_page;

    $sql = "SELECT * FROM students $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sql);

    // Bind WHERE clause parameters first
    $param_index = 1;
    foreach ($params as $value) {
        $stmt->bindValue($param_index++, $value, PDO::PARAM_STR);
    }

    // Bind LIMIT and OFFSET as positional parameters
    $stmt->bindValue($param_index++, $per_page, PDO::PARAM_INT);
    $stmt->bindValue($param_index++, $offset, PDO::PARAM_INT);
    $stmt->execute();
    $students = $stmt->fetchAll();

    return [
        'students' => $students,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'search' => trim($search),
    ];
}

function get_student_by_id(int $student_id): ?array
{
    global $pdo;

    if (is_super_admin()) {
        $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
        $stmt->execute([$student_id]);
        return $stmt->fetch() ?: null;
    }

    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ? AND department_id = ?');
    $stmt->execute([$student_id, $_SESSION['department_id']]);
    return $stmt->fetch() ?: null;
}
