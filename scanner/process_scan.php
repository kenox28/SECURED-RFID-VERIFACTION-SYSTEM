<?php
// scanner/process_scan.php

require_once '../config/database.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input');
    }

    if (empty($input['rfid_uid'])) {
        throw new Exception('RFID UID is required');
    }

    $rfid_uid    = trim($input['rfid_uid']);
    $scan_method = isset($input['scan_method']) ? $input['scan_method'] : 'RFID';

    // ------------------------------------------------------------------
    // 1. Find the student — return ALL fields needed for the ID card
    // ------------------------------------------------------------------
    $stmt = $pdo->prepare("
        SELECT id, student_id, first_name, last_name, middle_name,
               course, year_level, section, contact_number, email,
               address, rfid_uid, photo, status, department_id
        FROM students
        WHERE rfid_uid = ?
        LIMIT 1
    ");
    $stmt->execute([$rfid_uid]);
    $student = $stmt->fetch();

    if (!$student) {
        // Log the unknown scan so admins can see it
        $logStmt = $pdo->prepare("INSERT INTO unknown_scans (scanned_value, scan_method) VALUES (?, ?)");
        $logStmt->execute([$rfid_uid, $scan_method]);

        echo json_encode([
            'success' => false,
            'message' => 'Unknown RFID — not registered in the system',
            'code'    => 'UNKNOWN_RFID'
        ]);
        exit();
    }

    if ($student['status'] !== 'Active') {
        echo json_encode([
            'success' => false,
            'message' => 'Student account is inactive',
            'code'    => 'INACTIVE_STUDENT',
            'student' => buildStudentCard($student)
        ]);
        exit();
    }

    // ------------------------------------------------------------------
    // 2. Find an ACTIVE session — auto-detect attendance type from session
    // ------------------------------------------------------------------
    $now        = date('H:i:s');
    $todayDate  = date('Y-m-d');
    $studentDepartmentId = $student['department_id'];

    if ($studentDepartmentId !== null) {
        $sessionStmt = $pdo->prepare("
            SELECT id, session_name, attendance_type, start_time, end_time
            FROM attendance_sessions
            WHERE status = 'ACTIVE'
              AND start_time <= ?
              AND end_time   >= ?
              AND (department_id = ? OR department_id IS NULL)
            ORDER BY start_time ASC
            LIMIT 1
        ");
        $sessionStmt->execute([$now, $now, $studentDepartmentId]);
    } else {
        $sessionStmt = $pdo->prepare("
            SELECT id, session_name, attendance_type, start_time, end_time
            FROM attendance_sessions
            WHERE status = 'ACTIVE'
              AND start_time <= ?
              AND end_time   >= ?
              AND department_id IS NULL
            ORDER BY start_time ASC
            LIMIT 1
        ");
        $sessionStmt->execute([$now, $now]);
    }
    $session = $sessionStmt->fetch();

    if (!$session) {
        // No time-matched session — try any ACTIVE session as fallback
        if ($studentDepartmentId !== null) {
            $fallbackStmt = $pdo->prepare("
                SELECT id, session_name, attendance_type, start_time, end_time
                FROM attendance_sessions
                WHERE status = 'ACTIVE'
                  AND (department_id = ? OR department_id IS NULL)
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $fallbackStmt->execute([$studentDepartmentId]);
        } else {
            $fallbackStmt = $pdo->prepare("
                SELECT id, session_name, attendance_type, start_time, end_time
                FROM attendance_sessions
                WHERE status = 'ACTIVE'
                  AND department_id IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $fallbackStmt->execute();
        }
        $session = $fallbackStmt->fetch();
    }

    if (!$session) {
        echo json_encode([
            'success' => false,
            'message' => 'No active session found. Please ask the admin to start a session.',
            'code'    => 'NO_ACTIVE_SESSION',
            'student' => buildStudentCard($student)
        ]);
        exit();
    }

    $attendance_type = $session['attendance_type'];
    $session_id      = $session['id'];

    // ------------------------------------------------------------------
    // 3. Prevent duplicate scan in the same session on the same day
    // ------------------------------------------------------------------
    $dupStmt = $pdo->prepare("
        SELECT id FROM attendance_logs
        WHERE student_id      = ?
          AND session_id      = ?
          AND attendance_type = ?
          AND DATE(scan_time) = ?
        LIMIT 1
    ");
    $dupStmt->execute([$student['id'], $session_id, $attendance_type, $todayDate]);
    $duplicate = $dupStmt->fetch();

    if ($duplicate) {
        echo json_encode([
            'success' => false,
            'message' => 'Already logged ' . $attendance_type . ' for this session today',
            'code'    => 'DUPLICATE_SCAN',
            'student' => buildStudentCard($student),
            'session' => $session
        ]);
        exit();
    }

    // ------------------------------------------------------------------
    // 4. Log the attendance
    // ------------------------------------------------------------------
    $insertStmt = $pdo->prepare("
        INSERT INTO attendance_logs
            (student_id, rfid_uid, attendance_type, session_id, scan_method, scan_time)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $insertStmt->execute([
        $student['id'],
        $rfid_uid,
        $attendance_type,
        $session_id,
        $scan_method
    ]);
    // ------------------------------------------------------------------
    // 5. Send attendance notification email to student
    // ------------------------------------------------------------------

    if (!empty($student['email'])) {

        $mail = new PHPMailer(true);

        try {
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;

            // YOUR GMAIL
            $mail->Username   = 'ebakunado.linaohealthcenter@gmail.com';

            // YOUR GMAIL APP PASSWORD
            $mail->Password   = 'yhfd becn tywa ncyy';

            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Sender
            $mail->setFrom('ebakunado.linaohealthcenter@gmail.com', 'RFID Attendance System');

            // Student Email
            $mail->addAddress(
                $student['email'],
                $student['first_name'] . ' ' . $student['last_name']
            );

            $mail->isHTML(true);

            $mail->Subject = 'Attendance Recorded Successfully';

            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding:20px;'>
                    <h2 style='color:#16a34a;'>Attendance Confirmed</h2>

                    <p>Hello <strong>{$student['first_name']}</strong>,</p>

                    <p>Your attendance has been successfully recorded by the RFID Attendance System.</p>

                    <table style='border-collapse: collapse; width:100%; margin-top:15px;'>
                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>Student ID</strong></td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$student['student_id']}</td>
                        </tr>

                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>Name</strong></td>
                            <td style='padding:8px; border:1px solid #ddd;'>
                                {$student['first_name']} {$student['last_name']}
                            </td>
                        </tr>

                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>Attendance Type</strong></td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$attendance_type}</td>
                        </tr>

                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>Session</strong></td>
                            <td style='padding:8px; border:1px solid #ddd;'>{$session['session_name']}</td>
                        </tr>

                        <tr>
                            <td style='padding:8px; border:1px solid #ddd;'><strong>Date & Time</strong></td>
                            <td style='padding:8px; border:1px solid #ddd;'>" . date('F d, Y h:i A') . "</td>
                        </tr>
                    </table>

                    <p style='margin-top:20px;'>
                        This is an automated notification from the RFID Attendance System.
                    </p>
                </div>
            ";

            $mail->send();

        } catch (Exception $e) {
            error_log('Attendance email failed: ' . $mail->ErrorInfo);
        }
    }
    echo json_encode([
        'success'  => true,
        'message'  => 'Attendance recorded — ' . $attendance_type,
        'code'     => 'SCAN_OK',
        'student'  => buildStudentCard($student),
        'session'  => [
            'id'              => $session['id'],
            'session_name'    => $session['session_name'],
            'attendance_type' => $session['attendance_type'],
        ]
    ]);

} catch (PDOException $e) {
    error_log('process_scan PDO error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error. Please contact the administrator.',
        'code'    => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code'    => 'ERROR'
    ]);
}

// ------------------------------------------------------------------
// Helper — shape the student data for the ID-card display
// ------------------------------------------------------------------
function buildStudentCard(array $s): array {
    $photoUrl = '';
    if (!empty($s['photo'])) {
        // Support absolute paths, relative paths, and URLs
        if (strpos($s['photo'], 'http') === 0) {
            $photoUrl = $s['photo'];
        } else {
            $photoUrl = '/uploads/students/' . ltrim($s['photo'], '/');
        }
    }

    return [
        'student_id'     => $s['student_id'],
        'full_name'      => trim($s['first_name'] . ' ' . ($s['middle_name'] ? $s['middle_name'][0] . '. ' : '') . $s['last_name']),
        'first_name'     => $s['first_name'],
        'last_name'      => $s['last_name'],
        'middle_name'    => $s['middle_name'],
        'course'         => $s['course'],
        'year_level'     => $s['year_level'],
        'section'        => $s['section'],
        'contact_number' => $s['contact_number'],
        'email'          => $s['email'],
        'address'        => $s['address'],
        'status'         => $s['status'],
        'photo_url'      => $photoUrl,
    ];
}
?>