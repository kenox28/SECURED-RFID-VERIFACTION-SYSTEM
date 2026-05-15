<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header('Location: /login.php');
    exit();
}

require_once __DIR__ . '/../config/database.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'RFID System' ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <!-- IMPORTANT FIX -->
    <link rel="stylesheet" href="/css/style.css">
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar">

    <div class="p-3 text-white fw-bold border-bottom">
        RFID SYSTEM
    </div>

    <a href="/pages/dashboard.php">Dashboard</a>
    <a href="/pages/students.php">Students</a>
    <a href="/pages/register_student.php">Register Student</a>
    <a href="/pages/profile.php">Profile</a>
    <a href="/pages/attendance/attendance_logs.php">Attendance Logs</a>
    <a href="/pages/attendance/attendance_sessions.php">Sessions</a>
    <a href="/pages/attendance/attendance_reports.php">Reports</a>
    <a href="/logout.php">Logout</a>

</div>

<!-- MAIN -->
<main class="main-content">

    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h4><?= $page_title ?? '' ?></h4>

        <button class="btn btn-sm btn-outline-secondary" onclick="toggleSidebar()">
            ☰
        </button>
    </div>

    <!-- PAGE CONTENT START -->