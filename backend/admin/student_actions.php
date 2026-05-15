<?php
// backend/admin/student_actions.php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../auth.php';

function get_students(string $search = '', int $page = 1, int $per_page = 10): array
{
    global $pdo;

    $where_clause = '';
    $params = [];

    if (trim($search) !== '') {
        $search_param = '%' . trim($search) . '%';
        $where_clause = 'WHERE student_id LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR course LIKE ?';
        $params = [$search_param, $search_param, $search_param, $search_param];
    }

    $count_sql = "SELECT COUNT(*) as total FROM students $where_clause";
    $stmt = $pdo->prepare($count_sql);
    $stmt->execute($params);
    $total_records = (int)$stmt->fetch()['total'];
    $total_pages = max(1, (int)ceil($total_records / $per_page));
    $offset = ($page - 1) * $per_page;

    $sql = "SELECT * FROM students $where_clause ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);

    foreach ($params as $index => $value) {
        $stmt->bindValue($index + 1, $value, PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$student_id]);
    return $stmt->fetch() ?: null;
}
