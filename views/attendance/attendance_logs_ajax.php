<?php
require_once __DIR__ . '/../layout.php';
header('Content-Type: application/json');

$filter_session = intval($_GET['session_id'] ?? 0);
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_type = $_GET['type'] ?? '';

$deptFilter = get_department_filter('asess', true);
$where = ['DATE(al.scan_time) = ?'];
$params = [$filter_date];

if ($deptFilter[0] !== '') {
    $where[] = $deptFilter[0];
    $params = array_merge($params, $deptFilter[1]);
}

if ($filter_session > 0) {
    $where[] = 'al.session_id = ?';
    $params[] = $filter_session;
}
if (in_array($filter_type, ['IN', 'OUT'])) {
    $where[] = 'al.attendance_type = ?';
    $params[] = $filter_type;
}

$whereSQL = implode(' AND ', $where);
$stmt = $pdo->prepare("SELECT al.id, al.rfid_uid, al.attendance_type, al.scan_method, al.scan_time, s.student_id AS student_code, s.first_name, s.last_name, s.course, s.year_level, s.section, asess.session_name FROM attendance_logs al JOIN students s ON al.student_id = s.id JOIN attendance_sessions asess ON al.session_id = asess.id WHERE $whereSQL ORDER BY al.scan_time DESC LIMIT 200");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$result = array_map(function ($r) {
    $dt = new DateTime($r['scan_time']);
    return [
        'id' => $r['id'],
        'rfid_uid' => $r['rfid_uid'],
        'attendance_type' => $r['attendance_type'],
        'scan_method' => $r['scan_method'],
        'scan_date' => $dt->format('M d, Y'),
        'scan_time_fmt' => $dt->format('h:i:s A'),
        'student_code' => $r['student_code'],
        'full_name' => $r['first_name'] . ' ' . $r['last_name'],
        'course' => $r['course'],
        'year_level' => $r['year_level'],
        'section' => $r['section'],
        'session_name' => $r['session_name'],
    ];
}, $rows);

echo json_encode($result);
