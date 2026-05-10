<?php
// scanner/process_scan.php

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['rfid_uid']) || !isset($input['scan_method'])) {
        throw new Exception('Invalid input');
    }

    $rfid_uid = $input['rfid_uid'];
    $scan_method = $input['scan_method'];

    // Check if RFID exists in students table
    $stmt = $pdo->prepare("SELECT id, status FROM students WHERE rfid_uid = ?");
    $stmt->execute([$rfid_uid]);
    $student = $stmt->fetch();

    if (!$student) {
        // Log unknown scan
        $stmt = $pdo->prepare("INSERT INTO unknown_scans (scanned_value, scan_method) VALUES (?, ?)");
        $stmt->execute([$rfid_uid, $scan_method]);
        throw new Exception('Unknown RFID scanned');
    }

    if ($student['status'] !== 'Active') {
        throw new Exception('Student is inactive');
    }

    // Log attendance
    $stmt = $pdo->prepare("INSERT INTO attendance_logs (student_id, rfid_uid, attendance_type, session_id, scan_method) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $student['id'],
        $rfid_uid,
        $input['attendance_type'] ?? 'IN',
        $input['session_id'] ?? 0,
        $scan_method
    ]);

    echo json_encode(['success' => true, 'message' => 'Scan processed successfully', 'student' => $student]);
} catch (PDOException $e) {
    if (str_contains($e->getMessage(), 'foreign key constraint fails')) {
        echo json_encode(['success' => false, 'message' => 'Invalid session ID. Please ensure an active session exists.', 'error' => $e->getMessage()]);
    } else {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}